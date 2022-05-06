<?php

/* Icinga DB Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Data;

use Exception;
use Icinga\Application\Logger;
use Icinga\Authentication\User\DomainAwareInterface;
use Icinga\User;
use Icinga\Web\Notification;
use Icinga\Data\Filter\Filter;
use ipl\Web\Control\SimpleSuggestions;

class UserSuggestions extends SimpleSuggestions
{
    protected $backends;

    protected $userGroupName;

    protected $userGroupBackend;

    /**
     * @param mixed $userGroupBackend
     */
    public function setUserGroupBackend($userGroupBackend)
    {
        $this->userGroupBackend = $userGroupBackend;

        return $this;
    }

    /**
     * @param string $userGroupName
     */
    public function setUserGroupName($userGroupName)
    {
        $this->userGroupName = $userGroupName;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBackends()
    {
        return $this->backends;
    }

    /**
     * @param mixed $backends
     */
    public function setBackends($backends)
    {
        $this->backends = $backends;

        return $this;
    }

    /**
     * @param $searchTerm
     *
     * @return \Generator|void
     */
    protected function fetchSuggestions($searchTerm, $exclude)
    {
        $count = 0;
        foreach ($this->getBackends() as $backend) {
            try {
                if ($backend instanceof DomainAwareInterface) {
                    $domain = $backend->getDomain();
                } else {
                    $domain = null;
                }

                $members = $this->userGroupBackend
                    ->select()
                    ->from('group_membership', ['user_name'])
                    ->where('group_name', $this->userGroupName)
                    ->fetchColumn();


                if (! empty($exclude)) {
                    $members = array_merge($members, $exclude);
                }

                $filter = Filter::matchAll(
                    Filter::where('user_name', $searchTerm),
                    Filter::not(Filter::where('user_name', $members))
                );

                $users = $backend->select(['user_name'])
                    ->limit(self::DEFAULT_LIMIT)
                    ->applyFilter($filter)
                    ->fetchColumn();

                foreach ($users as $userName) {
                    if ($count === self::DEFAULT_LIMIT) {
                        return;
                    }

                    $userObj = new User($userName);
                    if ($domain !== null) {
                        if ($userObj->hasDomain() && $userObj->getDomain() !== $domain) {
                            // Users listed in a user backend which is configured to be responsible for a domain should
                            // not have a domain in their username. Ultimately, if the username has a domain, it must
                            // not differ from the backend's domain. We could log here - but hey, who cares :)
                            continue;
                        } else {
                            $userObj->setDomain($domain);
                        }
                    }

                    $count++;

                    yield $userObj->getUsername();
                }
            } catch (Exception $e) {
                Logger::error($e);
                Notification::warning(sprintf(
                    t('Failed to fetch any users from backend %s. Please check your log'),
                    $backend->getName()
                ));
            }
        }
    }
}
