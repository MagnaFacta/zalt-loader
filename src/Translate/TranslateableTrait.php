<?php

/**
 *
 * @package    Zalt
 * @subpackage Loader\Translate
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Zalt\Loader\Translate;

/**
 * Add auto translate functions to a class
 *
 * @package    Zalt
 * @subpackage Loader\Translate
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
trait TranslateableTrait
{
    /**
     *
     * @var \Zend_Translate
     */
    protected $translate;

    /**
     *
     * @var \Zend_Translate_Adapter
     */
    protected $translateAdapter;

    /**
     *
     * @var Laminas\I18n\Translator\TranslatorInterface
     */
    protected $translator;
    
    /**
     * Copy from \Zend_Translate_Adapter
     *
     * Translates the given string
     * returns the translation
     *
     * @param  string             $text   Translation string
     * @param  string|\Zend_Locale $locale (optional) Locale/Language to use, identical with locale
     *                                    identifier, @see \Zend_Locale for more information
     * @return string
     */
    public function _($text, $locale = null)
    {
        if ($this->translator) {
            return $this->translator->translate($text, 'default', $locale);
        }
        return $this->translateAdapter->_($text, $locale);
    }

    /**
     * Function that checks the setup of this class/traight
     *
     * This function is not needed if the variables have been defined correctly in the
     * source for this object and theose variables have been applied.
     *
     * return @void
     */
    protected function initTranslateable()
    {
        if ($this->translator instanceof Laminas\I18n\Translator\TranslatorInterface) {
            // OK
            return;
        } else {
            // Clean up just in case
            $this->translator = null;
        }
        
        if ($this->translateAdapter instanceof \Zend_Translate_Adapter) {
            // OK
            return;
        }

        if ($this->translate instanceof \Zend_Translate) {
            // Just one step
            $this->translateAdapter = $this->translate->getAdapter();
            return;
        }

        if ($this->translate instanceof \Zend_Translate_Adapter) {
            // It does happen and if it is all we have
            $this->translateAdapter = $this->translate;
            return;
        }

        // Make sure there always is an adapter, even if it is fake.
        $this->translateAdapter = new \MUtil_Translate_Adapter_Potemkin();
    }

    /**
     * Copy from \Zend_Translate_Adapter
     *
     * Translates the given string using plural notations
     * Returns the translated string
     *
     * @see \Zend_Locale
     * @param  string             $singular Singular translation string
     * @param  string             $plural   Plural translation string
     * @param  integer            $number   Number for detecting the correct plural
     * @param  string|\Zend_Locale $locale   (Optional) Locale/Language to use, identical with
     *                                      locale identifier, @see \Zend_Locale for more information
     * @return string
     */
    public function plural($singular, $plural, $number, $locale = null)
    {
        if ($this->translator) {
            return $this->translator->translatePlural($singular, $plural, $count, 'default', $locale);
        }
        $args = func_get_args();
        return call_user_func_array(array($this->translateAdapter, 'plural'), $args);
    }
}