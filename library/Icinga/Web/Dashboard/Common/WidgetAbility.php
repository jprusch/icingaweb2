<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\Common;

trait WidgetAbility
{
    /**
     * A flag whether this widget has been disabled (affects only default home)
     *
     * @var bool
     */
    protected $disabled = false;

    /**
     * Set whether this widget should be disabled
     *
     * @param bool $disabled
     *
     * @return $this
     */
    public function setDisabled(bool $disabled): self
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Get whether this widget has been disabled
     *
     * @return bool
     */
    public function isDisabled(): bool
    {
        return $this->disabled;
    }
}
