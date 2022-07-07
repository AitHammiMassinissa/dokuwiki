<?php

namespace dokuwiki\plugin\bureaucracyau\test;

use \Doku_Form;

/**
 * @group plugin_bureaucracyau
 * @group plugins
 */
class bureaucracyau_field_date_test extends BureaucracyauTest
{

    public function dataProvider()
    {
        return [
            [
                'Date:@@dateLabel@@',
                'date "dateLabel"',
                '15-05-2018',
                'Date:15-05-2018',
                [],
                'valid date',
            ],
            [
                'Date:@@dateLabel@@',
                'date "dateLabel"',
                '15.05.2018',
                null,
                ['dateLabel'],
                'invalid date',
            ],
            [
                'Date: @DATE(@@dateLabel@@)@',
                'date "dateLabel"',
                '15-02-2018',
                'Date: 2018/02/15 00:00',
                [],
                'formatted date with $conf[\'dformat\'] format',
            ],
            [
                'Month: @DATE(@@dateLabel@@,%%m)@',
                'date "dateLabel"',
                '15-02-2018',
                'Month: 02',
                [],
                'formatted date with custom format',
            ],
        ];
    }

    /**
     * @dataProvider dataProvider
     *
     * @param string $templateSyntax
     * @param string $formSyntax
     * @param string $postedValue
     * @param string $expectedWikiText
     * @param string $expectedValidationErrors
     * @param string $msg
     *
     */
    public function test_field_date_submit(
        $templateSyntax,
        $formSyntax,
        $postedValue,
        $expectedWikiText,
        $expectedValidationErrors,
        $msg
    ) {
        $actualValidationErrors = [];

        $actualWikiText = parent::send_form_action_template(
            $formSyntax,
            $templateSyntax,
            $actualValidationErrors,
            $postedValue
        );

        if (empty($expectedValidationErrors)) {
            $this->assertEquals($expectedWikiText, $actualWikiText, $msg);
        }
        $this->assertEquals($expectedValidationErrors, $actualValidationErrors, $msg);
    }

    public function test_field_date_render()
    {
        $formSyntax = 'date "dateLabel"';
        $instr = p_get_instructions("<form>\n$formSyntax\n</form>");

        $actualHTML = p_render('xhtml', $instr, $info);

        $expectedFieldHTML = '<label><span>dateLabel <sup>*</sup></span> <input type="text" name="bureaucracyau[0]" class="datepicker edit required" maxlength="10" required="required" /></label>';
        $expectedHTML = self::FORM_PREFIX_HTML . "\n$expectedFieldHTML\n" . self::FORM_SUFFIX_HTML;
        $this->assertEquals(trim($expectedHTML), trim($actualHTML));
    }
}
