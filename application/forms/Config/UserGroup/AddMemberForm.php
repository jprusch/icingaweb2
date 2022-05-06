<?php
/* Icinga Web 2 | (c) 2022 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\UserGroup;

use Exception;
use Icinga\Exception\NotFoundError;
use Icinga\Web\Notification;
use ipl\Web\Control\SimpleSearchField;

/**
 * Form for adding one or more group members
 */
class AddMemberForm extends SimpleSearchField
{
    protected $backend;

    protected $groupName;

    /**
     * @param mixed $backend
     */
    public function setBackend($backend)
    {
        $this->backend = $backend;

        return $this;
    }

    /**
     * @param mixed $groupName
     */
    public function setGroupName($groupName)
    {
        $this->groupName = $groupName;

        return $this;
    }

    /**
     * Insert the members for the group
     *
     * @return  bool
     */
    public function onSuccess()
    {
        $q = $this->getValue($this->getSearchParameter());
        if (empty($q)) {
            Notification::error(t('Please provide at least one username'));
            return false;
        }

        $userNames = array_unique(explode(self::TERM_SEPARATOR, urldecode($q)));
        $userNames = array_map('trim', $userNames);

        $single = null;
        foreach ($userNames as $userName) {
            try {
                $this->backend->insert(
                    'group_membership',
                    [
                        'group_name'    => $this->groupName,
                        'user_name'     => $userName
                    ]
                );
            } catch (NotFoundError $e) {
                throw $e; // Trigger 404, the group name is initially accessed as GET parameter
            } catch (Exception $e) {
                Notification::error(sprintf(
                    t('Failed to add "%s" as group member for "%s"'),
                    $userName,
                    $this->groupName
                ));

                return false;
            }

            $single = $single === null;
        }

        if ($single) {
            Notification::success(sprintf(t('Group member "%s" added successfully'), $userName));
        } else {
            Notification::success(t('Group members added successfully'));
        }

        return true;
    }
}
