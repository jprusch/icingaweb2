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

    /** @var Less_Tree_Variable */
    protected $variable;

    /**
     * A flag to determine whether this is a less tree variable
     *
     * @var bool
     */
    protected $isVariable = true;

    /**
     * Caches already resolved variable references as a {@see ColorProp} objects
     *
     * @var ColorProp[]
     */
    protected static $resolvedVarRefs = [];

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
                $frameVariables = $env->frames[sizeof($env->frames) - 1]->_variables;

                $lastVarRef = $v->name;
                foreach (array_reverse(Visitor::$visitorVarReferences) as $source => $reference) {
                    if (self::isRefResolved($source) || ! isset($frameVariables[$source])
                        || ($source !== $v->name && $lastVarRef !== $source)
                    ) {
                        continue;
                    }

                    $variable = $frameVariables[$source];
                    // We are only interested in less tree rule objects
                    if (! $variable instanceof \Less_Tree_Rule || ! $variable->value instanceof Less_Tree_Color) {
                        continue;
                    }

                    $lastVarRef = $reference;
                    // Update the name of the value to the already registered visitor reference
                    $variable->value->name = $reference;

                    $variable = ColorProp::fromColor(clone $variable->value);
                    $variable->setName($source);

                    // Cache the resolved version of the source variable
                    self::$resolvedVarRefs[$source] = $variable;

                    if (! isset(Visitor::$visitorVarReferences[$reference])) {
                        /**
                         * This is happening because the very first source Variable, which contains the actual color
                         * code definition, doesn't hold a reference to any other. However, we have to cast it to a
                         * {@see ColorProp} in order to get the references' resolution to work correctly there.
                         */
                        $refObj = $frameVariables[$reference];
                        if ($refObj instanceof \Less_Tree_Rule && $refObj->value instanceof Less_Tree_Color) {
                            // Mark also the uppermost variable of the references as resolved
                            self::$resolvedVarRefs[$reference] = ColorProp::fromColor(clone $refObj->value);
                        }

                        // We have resolved the references backwards to the initial variable,
                        // so we need to break this here off to eliminate unnecessary looping iterations
                        break;
                    }
                }
            }

            return ColorProp::fromColor($compiled)
                ->setIndex($v->index)
                ->setName($v->name);
        }

        return $compiled;
    }

    /**
     * Get whether the given variable ref is already resolved
     *
     * @param string $name
     *
     * @return bool
     */
    public static function isRefResolved(string $name): bool
    {
        return isset(self::$resolvedVarRefs[self::getValidName($name)]);
    }

    /**
     * Get all less tree variable resolved references
     *
     * @return ColorProp[]
     */
    public static function getResolvedVarRefs(): array
    {
        return self::$resolvedVarRefs;
    }

    /**
     * Get the resolved ColorProp for the given name
     *
     * @param string $name
     *
     * @return ColorProp
     */
    public static function getResolvedRefFor(string $name): ColorProp
    {
        if (! self::isRefResolved($name)) {
            throw new \RuntimeException(sprintf(
                'Trying to access unresolved variable reference for "%s"',
                self::getValidName($name)
            ));
        }

        return self::getResolvedVarRefs()[self::getValidName($name)];
    }

    /**
     * Just ensures that the given name begins with the "@" char
     *
     * @param string $name
     *
     * @return string
     */
    public static function getValidName(string $name): string
    {
        if ($name[0] !== '@') {
            $name = '@' . $name;
        }

        return $name;
    }
}
