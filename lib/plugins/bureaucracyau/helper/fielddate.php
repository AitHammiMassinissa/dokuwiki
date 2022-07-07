<?php
/**
 * Class helper_plugin_bureaucracyau_fielddate
 *
 * A date in the format DD-MM-YYYY, provides a date picker
 */
class helper_plugin_bureaucracyau_fielddate extends helper_plugin_bureaucracyau_fieldtextbox {
    /**
     * Arguments:
     *  - cmd
     *  - label
     *  - ^ (optional)
     *
     * @param array $args The tokenized definition, only split at spaces
     */
    public function initialize($args) {
        parent::initialize($args);
        $attr = array(
            'class' => 'datepicker edit',
            'maxlength'=>'10'
        );
        if(!isset($this->opt['optional'])) {
            $attr['required'] = 'required';
            $attr['class'] .= ' required';
        }
        $this->tpl = form_makeTextField('@@NAME@@', '@@VALUE@@', '@@DISPLAY@@', '@@ID@@', '@@CLASS@@', $attr);
    }

    /**
     * Validate field input
     *
     * @throws Exception when empty or wrong date format
     */
    protected function _validate() {
        parent::_validate();

        $value = $this->getParam('value');
        if (!is_null($value) && !preg_match('/^\d{2}-\d{2}-\d{4}$/', $value)) {
            throw new Exception(sprintf($this->getLang('e_date'),hsc($this->getParam('display'))));
        }
    }
}
