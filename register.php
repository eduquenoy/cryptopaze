<?php

$REGISTER_LTI2 = array(
"name" => "Cryptopaze",
"FontAwesome" => "fa-server",
"short_name" => "Cryptopaze Tool",
"description" => __("Interface tool for Topaze Applications."),
    // By default, accept launch messages..
    "messages" => array("launch"),
    "privacy_level" => "name_only",  // anonymous, name_only, public
    "license" => "Apache",
    "languages" => array(
        "English", "French"
    ),
    "source_url" => "https://github.com/eduquenoy/cryptopaze",
    // For now Tsugi tools delegate this to /lti/store
    "placements" => array(
        /*
        "course_navigation", "homework_submission",
        "course_home_submission", "editor_button",
        "link_selection", "migration_selection", "resource_selection",
        "tool_configuration", "user_navigation"
        */
    ),
    "screen_shots" => array(
    //    "store/screen-01.png",
    //    "store/screen-02.png",
    //    "store/screen-03.png"
    )

);
