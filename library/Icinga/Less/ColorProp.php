<?php
/* Icinga Web 2 | (c) 2022 Icinga Development Team | GPLv2+ */

namespace Icinga\Less;

use Less_Tree;
use Less_Tree_Call;
use Less_Tree_Color;
use Less_Tree_Keyword;

/**
 * ColorProp renders Less colors as CSS var() function calls
 *
 * It extends {@link Less_Tree_Color} so that Less functions that take a Less_Tree_Color as an argument do not fail.
 */
class ColorProp extends Less_Tree_Color
{
    /** @var Less_Tree Color with which we created the ColorProp */
    protected $color;

    /** @var int */
    protected $index;

    /** @var string Color variable name */
    protected $name;

    /** @var bool Whether we have already generated a CSS output from this color prop */
    protected $generated = false;

    /** @var string Closing brace of this less tree call */
    protected $closingBraces = '';

    /** @var bool Whether the last visitor less var references required by this prop is already rendered */
    protected static $lastVarRendered = false;

    public function __construct()
    {
    }

    /**
     * @param Less_Tree_Color $color
     *
     * @return static
     */
    public static function fromColor(Less_Tree_Color $color)
    {
        $self = new static();
        $self->color = $color;

        foreach ($color as $k => $v) {
            if ($k === 'name') { // In order to trim any unwanted chars from the name
                $self->setName($v);
            } else {
                $self->$k = $v;
            }
        }

        return $self;
    }

    public static function fromColorProp(ColorProp $colorProp)
    {
        $self = new static();

        $self->color = $colorProp;

        return $self;
    }

    /**
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param int $index
     *
     * @return $this
     */
    public function setIndex($index)
    {
        $this->index = $index;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        if ($name[0] === '@') {
            $name = substr($name, 1);
        }

        $this->name = $name;

        return $this;
    }

    public function genCSS($output)
    {
        if ($this->color instanceof ColorProp || isset($this->color->name)) {
            $colorPropName = $this->color->name;
            if ($colorPropName[0] !== '@') {
                $colorPropName = '@' . $colorPropName;
            }

            if (ColorPropOrVariable::isRefResolved($this->getName())) {
                $frameVar = ColorPropOrVariable::getResolvedRefFor($this->getName());
                if (isset($frameVar->color->name) && $colorPropName !== $frameVar->color->name) {
                    $colorPropName = $frameVar->color->name;
                }
            }

            if (substr($colorPropName, 1) !== $this->getName() && ColorPropOrVariable::isRefResolved($colorPropName)) {
                $key = new Less_Tree_Keyword('var(--' . $this->getName());
                $key->genCSS($output);
                $output->add(', ');

                $this->generated = true;
                $this->closingBraces = ')';

                $fv = ColorPropOrVariable::getResolvedRefFor($colorPropName);
                $fv->genCSS($output);
            }
        }

        if (! $this->generated) {
            $css = (new Less_Tree_Call(
                'var',
                [
                    new Less_Tree_Keyword('--' . $this->getName()),
                    // Use the Less_Tree_Color with which we created the ColorProp so that we don't get into genCSS() loops.
                    $this->color
                ],
                $this->getIndex()
            ));

            $css->genCSS($output);

            self::$lastVarRendered = true;
            $this->closingBraces = null;
        }

        if (self::$lastVarRendered && $this->closingBraces) {
            $output->add($this->closingBraces);

            // This color prop may be used by other variables too, so we have to reset it here
            $this->generated = false;
        }
    }
}
