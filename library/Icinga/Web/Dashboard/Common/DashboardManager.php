<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\Common;

use Icinga\Application\Modules;
use Icinga\Exception\Http\HttpNotFoundException;
use Icinga\Exception\ProgrammingError;
use Icinga\Model;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\DashboardHome;
use Icinga\Web\Dashboard\Pane;
use Icinga\Util\DBUtils;
use ipl\Stdlib\Filter;

trait DashboardManager
{
    use DashboardUserManager;

    /**
     * A list of default panes loaded from monitoring|icingadb module
     *
     * @var Pane[]
     */
    private static $defaultPanes = [];

    /**
     * Load the given or all homes (null)
     *
     * @param ?string $name
     *
     * @return void
     */
    public function load(string $name = null)
    {
        $query = Model\Home::on(DBUtils::getConn());
        $query->filter(Filter::equal('icingaweb_dashboard_owner.id', $this::getUser()->getAdditional('id')));

        if ($name !== null) {
            $query->filter(Filter::equal('name', $name));

            /** @var Model\Home $row */
            if (($row = $query->first()) === null) {
                if ($name === DashboardHome::DEFAULT_HOME) {
                    $home = $this->initGetDefaultHome();
                } else {
                    throw new HttpNotFoundException(t('Home "%s" not found'), $name);
                }
            } else {
                $home = DashboardHome::create($row);
                $this->addEntry($home);
            }

            $this->activateHome($home);
            $home->loadDashboardEntries();
        } else {
            foreach ($query as $row) {
                $this->addEntry(DashboardHome::create($row));
            }

            if (($firstHome = $this->rewindEntries())) {
                $this->activateHome($firstHome);
                $firstHome->loadDashboardEntries();
            }
        }

       Modules\DashletManager::deployDashlets();
    }

    /**
     * Generate the sha1 hash of the provided string
     *
     * @param string $name
     *
     * @return string
     */
    public static function getSHA1(string $name): string
    {
        return sha1($name, true);
    }

    public function loadDashboardEntries(string $name = '')
    {
        $home = $this->getEntry($name);
        $home->loadDashboardEntries();

        return $this;
    }

    /**
     * Activates the given home and deactivates all other active homes
     *
     * @param DashboardHome $home
     *
     * @return $this
     */
    public function activateHome(DashboardHome $home): self
    {
        $activeHome = $this->getActiveHome();
        if ($activeHome && $activeHome->getName() !== $home->getName()) {
            $activeHome->setActive(false);
        }

        $home->setActive();

        return $this;
    }

    /**
     * Get the active home currently being loaded
     *
     * @return ?DashboardHome
     */
    public function getActiveHome()
    {
        /** @var DashboardHome $home */
        foreach ($this->getEntries() as $home) {
            if ($home->getActive()) {
                return $home;
            }
        }

        return null;
    }

    public function removeEntry($home)
    {
        $name = $home instanceof DashboardHome ? $home->getName() : $home;
        if (! $this->hasEntry($name)) {
            throw new ProgrammingError('Trying to remove invalid dashboard home "%s"', $name);
        }

        $home = $home instanceof DashboardHome ? $home : $this->getEntry($home);
        $home->removeEntries();

        if ($home->getName() !== DashboardHome::DEFAULT_HOME) {
            DBUtils::getConn()->delete(DashboardHome::TABLE, ['id = ?' => $home->getUuid()]);
        } elseif (! $home->isDisabled()) {
            DBUtils::getConn()->update(DashboardHome::TABLE, ['disabled' => DBUtils::bool2BoolEnum(true)], [
                'id = ?' => $home->getUuid()
            ]);
        }

        return $this;
    }

    public function manageEntry($entry, BaseDashboard $origin = null, $manageRecursive = false)
    {
        $conn = DBUtils::getConn();
        $homes = is_array($entry) ? $entry : [$entry];

        /** @var DashboardHome $home */
        foreach ($homes as $home) {
            if (! $this->hasEntry($home->getName())) {
                // Highest priority is 0, so count($entries) are always lowest prio + 1
                $priority = $home->getName() === DashboardHome::DEFAULT_HOME ? 0 : count($this->getEntries());
                $conn->insert(DashboardHome::TABLE, [
                    'user_id'  => self::getUser()->getAdditional('id'),
                    'name'     => $home->getName(),
                    'label'    => $home->getTitle(),
                    'priority' => $priority,
                    'type'     => $home->getType() !== Dashboard::SYSTEM ? $home->getType() : Dashboard::PRIVATE_DS
                ]);

                $home->setUuid($conn->lastInsertId());
            } else {
                $conn->update(DashboardHome::TABLE, [
                    'label'    => $home->getTitle(),
                    'priority' => $home->getPriority(),
                    'disabled' => DBUtils::bool2BoolEnum(false)
                ], ['id = ?' => $home->getUuid()]);
            }
        }

        return $this;
    }

    /**
     * Get and|or init the default dashboard home
     *
     * @return DashboardHome
     */
    public function initGetDefaultHome(): DashboardHome
    {
        if ($this->hasEntry(DashboardHome::DEFAULT_HOME)) {
            return $this->getEntry(DashboardHome::DEFAULT_HOME);
        }

        $default = new DashboardHome(DashboardHome::DEFAULT_HOME);
        $this->manageEntry($default);
        $this->addEntry($default);

        return $default;
    }
}
