<?php

namespace ImLiam\Modifiers\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ImLiam\Modifiers\HasModifiers;

class ModifierTest extends TestCase
{
    /** @test */
    public function a_method_that_is_not_a_registered_alias_will_have_no_modifiers()
    {
        $this->assertEmpty(@Example::returnAllModifiersWithoutAlias());
    }

    /** @test */
    public function a_method_that_is_not_a_registered_alias_will_have_modifiers()
    {
        $this->assertEquals(['@'], @Example::returnAllModifiersWithAlias());
    }

    /** @test */
    public function modifiers_are_not_required_to_be_used()
    {
        Example::setValue('test');
        $this->assertEquals('test', Example::$value);
    }

    /** @test */
    public function the_exclamation_mark_modifier_can_be_used()
    {
        ! Example::setValue('test');
        $this->assertEquals('test!', Example::$value);
    }

    /** @test */
    public function the_at_symbol_modifier_can_be_used()
    {
        @Example::setValue('test');
        $this->assertEquals('test@', Example::$value);
    }

    /** @test */
    public function the_tilde_modifier_can_be_used()
    {
        ~Example::setValue('test');
        $this->assertEquals('test~', Example::$value);
    }

    /** @test */
    public function the_plus_modifier_can_be_used()
    {
        +Example::setValue('test');
        $this->assertEquals('test+', Example::$value);
    }

    /** @test */
    public function the_minus_modifier_can_be_used()
    {
        -Example::setValue('test');
        $this->assertEquals('test-', Example::$value);
    }

    /** @test */
    public function multiple_modifiers_can_be_used_at_once()
    {
        ! @+-Example::setValue('test');
        $this->assertEquals('test!@+-', Example::$value);
    }

    /** @test */
    public function the_at_symbol_modifier_can_return_a_value()
    {
        $this->assertEquals('A value has been returned.', @Example::returnAModifiedValue());
    }
}

class Example
{
    use HasModifiers;

    public static $modifierAliases = [
        [self::class, 'setValue'],
        [self::class, 'returnAModifiedValue'],
        [self::class, 'returnAllModifiersWithAlias'],
    ];

    /**
     * @var string
     */
    public static $value = '';

    public static function setValue(string $value)
    {
        if (static::hasModifier('!')) {
            $value .= '!';
        }

        if (static::hasModifier('@')) {
            $value .= '@';
        }

        if (static::hasModifier('~')) {
            $value .= '~';
        }

        if (static::hasModifier('+')) {
            $value .= '+';
        }

        if (static::hasModifier('-')) {
            $value .= '-';
        }

        static::$value = $value;

        return 0; // Necessary for the tilde modifier
    }

    public static function returnAllModifiersWithoutAlias()
    {
        return static::getModifiers();
    }

    public static function returnAllModifiersWithAlias()
    {
        return static::getModifiers(); // Return value will only work with the @ modifier
    }

    public static function returnAModifiedValue()
    {
        return 'A value has been returned.';
    }
}
