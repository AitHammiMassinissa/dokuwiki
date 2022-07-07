/**
 * Init datepicker for all date fields
 */

jQuery(function(){
    jQuery('.bureaucracyau__plugin .datepicker').datepicker({
        dateFormat: "dd-mm-yy",
        changeMonth: true,
        changeYear: true
    });
});
