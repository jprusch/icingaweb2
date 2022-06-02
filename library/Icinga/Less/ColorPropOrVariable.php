<?php
/* Icinga Web 2 | (c) 2022 Icinga Development Team | GPLv2+ */

namespace Icinga\Less;

use Less_Tree;
use Less_Tree_Color;
use Less_Tree_Variable;

/**
 * Compile a Less variable to {@link ColorProp} if it is a color
 */
class ColorPropOrVariable extends Less_Tree
{
    public $type = 'Variable';

    /** @var array All less tree variable rules pushed to the latest frame stack */
    public static $frameVariables = [];

    /** @var Less_Tree_Variable */
    protected $variable;

    /**
     * A flag to determine whether this is a less tree variable
     *
     * @var bool
     */
    protected $isVariable = true;

    /**
     * @return Less_Tree_Variable
     */
    public function getVariable()
    {
        return $this->variable;
    }

    /**
     * @param Less_Tree_Variable $variable
     *
     * @return $this
     */
    public function setVariable(Less_Tree_Variable $variable)
    {
        $this->variable = $variable;

        return $this;
    }

    /**
     * Set whether this is a less tree variable
     *
     * @param bool $isVariable
     *
     * @return $this
     */
    public function setIsVariable(bool $isVariable): self
    {
        $this->isVariable = $isVariable;

        return $this;
    }

    /**
     * Get whether this a less tree variable
     *
     * @return bool
     */
    public function isVariable(): bool
    {
        return $this->isVariable;
    }

    public function compile($env)
    {
        $v = $this->getVariable();

        if ($v->name[1] === '@') {
            // Evaluate variable variable as in Less_Tree_Variable:28.
            $vv = new Less_Tree_Variable(substr($v->name, 1), $v->index + 1, $v->currentFileInfo);
            // Overwrite the name so that the variable variable is not evaluated again.
            $v->name = '@' . $vv->compile($env)->value;
        }

        $compiled = $v->compile($env);

        if ($compiled instanceof ColorProp) {
            // We may already have a ColorProp, which is the case with mixin calls.
            return $compiled;
        }

        if ($compiled instanceof Less_Tree_Color) {
            if ($this->isVariable() && isset($compiled->name) && $compiled->name !== $v->name) {
                // Retrieve all variables of the last pushed frame
                self::$frameVariables = $env->frames[sizeof($env->frames) - 1]->_variables;

                foreach (Visitor::$visitorVarReferences as $source => $reference) {
                    if (! isset(self::$frameVariables[$source])) {
                        continue;
                    }

                    $variable = &self::$frameVariables[$source];
                    // We are only interested in less tree rule objects
                    if ($variable instanceof ColorProp || ! $variable->value instanceof Less_Tree_Color) {
                        continue;
                    }

                    // Update the name of the value to the already registered visitor reference
                    $variable->value->name = $reference;

                    $variable = ColorProp::fromColor(clone $variable->value);
                    $variable->setName($source);

                    if (! isset(Visitor::$visitorVarReferences[$reference])) {
                        $refObj = &self::$frameVariables[$reference];
                        if ($refObj instanceof ColorProp || ! $refObj->value instanceof Less_Tree_Color) {
                            continue;
                        }

                        $refObj = ColorProp::fromColor(clone $refObj->value);
                    }
                }
            }

            return ColorProp::fromColor($compiled)
                ->setIndex($v->index)
                ->setName($v->name);
        }

        return $compiled;
    }
}
