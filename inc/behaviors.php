<?php

add_filter("waboot_add_behaviors","waboot_behaviors");
function waboot_behaviors($behaviors){

	$behaviors[] = array(
		"name" => "show-title",
        "title" => __("Display page title","waboot"),
        "desc" => __("Default rendering value for page title","waboot"),
        "options" => array(
            array(
	            "name" => __("Yes"),
                "value" => 1
            ),
            array(
	            "name" => __("No"),
                "value" => 0
            )
        ),
        "type" => "select",
        "default" => 1,
        "valid" => array("page","-{home}")
	);

	$behaviors[] = array(
		"name" => "title-position",
        "title" => __("Title position","waboot"),
        "desc" => __("Default title positioning in pages","waboot"),
        "type" => "select",
        "options" => array(
            array(
	            "name" => __("Above primary","waboot"),
                "value" => "top"
			),
            array(
	            "name" => __("Below primary","waboot"),
                "value" => "bottom"
			)
		),
        "default" => "top",
        "valid" => array("page")
	);

    $body_layouts = wbf_sanitize_of_array_values(waboot_get_sidebar_layouts());
	$behaviors[] = array(
		"name" => "layout",
        "title" => __("Body layout","waboot"),
        "desc" => __("Default body layout for posts and pages","waboot"),
        "options" => $body_layouts['values'],
        "type" => "select",
        "default" => $body_layouts['default'],
        "valid" => array("post","page","-{home}"),
	);

    /***********************************************
     ***************** SAMPLES *********************
     ***********************************************/

    /**
     * SINGLE CHECKBOX
     */
    /*$behaviors[] = array(
        "name" => "testcheck",
        "title" => "Test Checkboxes",
        "desc" => "This is a test checkbox",
        "type" => "checkbox",
        "default" => "1",
        "valid" => array("post","page")
    );*/

    /**
     * MULTIPLE CHECKBOX
     */
    /*$behaviors[] = array(
        "name" => "testmulticheck",
        "title" => "Test Checkboxes",
        "desc" => "This is a test checkbox",
        "type" => "checkbox",
        "options" => array(
            array(
                "name" => "test1",
                "value" => "test1"
            ),
            array(
                "name" => "test2",
                "value" => "test2"
            ),
        ),
        "default" => "test1",
        "valid" => array("post","page")
    );*/

    /**
     * RADIO
     */
    /*$behaviors[] = array(
		"name" => "testradio",
        "title" => "Test Radio",
        "desc" => "This is a test radio",
        "type" => "radio",
        "options" => array(
            array(
                "name" => "test1",
                "value" => "test1"
            ),
            array(
                "name" => "test2",
                "value" => "test2"
            ),
        ),
        "default" => "test2",
        "valid" => array("post","page")
	);*/

    /**
     * TEXT
     */
	/*$behaviors[] = array(
		"name" => "testinput",
        "title" => "Test Input",
        "desc" => "This is a test input",
        "type" => "text",
        "default" => "testme!",
        "valid" => array("post","page")
	);*/

    /**
     * TEXTAREA
     */
	/*$behaviors[] = array(
		"name" => "testarea",
        "title" => "Test Input",
        "desc" => "This is a test textarea",
        "type" => "textarea",
        "default" => "testme!",
        "valid" => array("post","page")
	);*/

	return $behaviors;
}