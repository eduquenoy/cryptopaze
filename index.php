<?php
require_once "../config.php";
?>
<script type="text/javascript">
function myFunction() {
  /* Get the text field */
  var copyText = document.getElementById("myInput");

  /* Select the text field */
  copyText.select();
  copyText.setSelectionRange(0, 99999); /*For mobile devices*/

  /* Copy the text inside the text field */
  //document.execCommand("copy");
  textToClipboard(copyText.value);
  /* Alert the copied text */
  alert("Copied the text: " + copyText.value);
} 
function textToClipboard (text) {
var dummy = document.createElement("textarea");
document.body.appendChild(dummy);
dummy.value = text;
dummy.select();
document.execCommand("copy");
document.body.removeChild(dummy);
}

</script>
<style type="text/css">
#myButton{
    color:blue;
}
</style>

<?php
$debug = FALSE;
// The Tsugi PHP API Documentation is available at:
// http://do1.dr-chuck.com/tsugi/phpdoc/

use \Tsugi\Core\LTIX;
use \Tsugi\Util\Net;
use \Tsugi\Core\Settings;
use \Tsugi\UI\SettingsForm;

// No parameter means we require CONTEXT, USER, and LINK
$LAUNCH = LTIX::requireData();

// If settings were updated
if ( SettingsForm::handleSettingsPost() ) {
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

// Handle Post Data
$p = $CFG->dbprefix;
$old_code = $LAUNCH->link->getJsonKey('code', '');
//$codeTAC = $LAUNCH->link->settingsGet('code', $old_code);//codeTAC = Code Topaze Application Code
$codeTAC = $LAUNCH->link->settingsGet('code');//codeTAC = Code Topaze Application Code
$send_grade = $LAUNCH->link->settingsGet('grade');//Retrouve le réglage du panneau de config
$urlTAU =  $LAUNCH->link->settingsGet('url');//Retrouve le réglage du panneau de config = url Topaze Application Url

$match = $LAUNCH->link->settingsGet('match');
$ip = Net::getIP();

if ( isset($_POST['clear']) && $USER->instructor ) {
    $PDOX->queryDie("DELETE FROM {$p}cryptopaze WHERE link_id = :LI",
            array(':LI' => $LINK->id)
    );
    $_SESSION['success'] = 'Data cleared';
    header( 'Location: '.addSession('index.php') ) ;
    return;
} 
if(isset($_POST['grade'])){ //Le 'grade' est doit être compris entre 0.0 et 1.0
    $toto64=$_POST['grade'];
    $toto = base64_decode($toto64);
    $toto_JSON = json_decode($toto,TRUE);
    $flag_error_email=FALSE;
    if(strcmp($USER->email,$toto_JSON["email"])==0){
            $toto = intval($toto_JSON["note"])/100; //Mise à l'échelle de la note
    }
    else{
        $toto = 0;
        $flag_error_email=TRUE;
    }
    //Décodage
 //Fonctions de décodage json et base64 : https://www.php.net/manual/fr/function.json-decode.php et https://www.php.net/manual/fr/function.base64-decode.php

//    $grade = ($_POST['grade']);//Transformation en valeur numérique 
        $grade = gradeDecode($_POST['grade']);//Transformation en valeur numérique et décryptage 
        $message="";
        if($flag_error_email){
            $message="email error";
        }
        $_SESSION['success'] = __('Grade found = '.$toto." and ".$grade." Code TAC = ".$codeTAC." ".$message);

//$grade = 0.23;
    //$RESULT->gradeSend($grade, false);//Ecriture de la note dans le bulletin de note du LMS
    $RESULT->gradeSend($toto, false);//Ecriture de la note dans le bulletin de note du LMS
    
    header( 'Location: '.addSession('index.php') ) ;
    return;
}
if ( isset($_POST['code']) ) { // Student
    if ( $old_code != $_POST['code'] ) {
        $_SESSION['error'] = __('Code incorrect');
        header( 'Location: '.addSession('index.php') ) ;
        return;
    }

    if ( strlen($match) > 0 && substr($match, 0, 1) == '/' ) {
        if (!preg_match($match, $ip) ) {
            $_SESSION['error'] = __('IP Address '.$ip.' does not match (regex).');
            header( 'Location: '.addSession('index.php') ) ;
            return;
        }
    }

    if ( strlen($match) > 0 && substr($match, 0, 1) != '/' ) {
        if ( strpos($match, $ip) === false ) {
            $_SESSION['error'] = __('IP Address '.$ip.' does not match.');
            header( 'Location: '.addSession('index.php') ) ;
            return;
        }
    }
    // Passed all the tests..
    $PDOX->queryDie("INSERT INTO {$p}cryptopaze
        (link_id, user_id, ipaddr, cryptopaze, updated_at)
        VALUES ( :LI, :UI, :IP, NOW(), NOW() )
        ON DUPLICATE KEY UPDATE updated_at = NOW()",
        array(
            ':LI' => $LINK->id,
            ':UI' => $USER->id,
            ':IP' => Net::getIP()
        )
    );

  /*  if ( $send_grade && isset($LAUNCH->link) && $LAUNCH->link ) {
        if ($LAUNCH->result && $LAUNCH->result->id && $RESULT->grade < 1.0 ) {
            $RESULT->gradeSend(1.0, false);
        }
    }
*/
    $_SESSION['success'] = __('Attendance Recorded...');
    header( 'Location: '.addSession('index.php') ) ;
    return;
}

// Prepare for view
if ( $USER->instructor ) {
    $rows = $PDOX->allRowsDie("SELECT A.user_id,cryptopaze,A.ipaddr, displayname, email
            FROM {$p}cryptopaze AS A
            JOIN {$p}lti_user AS U ON U.user_id = A.user_id
            WHERE link_id = :LI ORDER BY cryptopaze DESC, user_id",
            array(':LI' => $LINK->id)
    );
} else {
    $rows = false;
}

$menu = false;
if ( $USER->instructor ) {
    $menu = new \Tsugi\UI\MenuSet();
    $menu->addRight(__('Settings'), '#', /* push */ false, SettingsForm::attr());
}

//****************** Préparer la structure .json ***********
//On a besoin de l'ID du l'usage, du nom/prénom et du code de l'application Topaze
/*$USER->displayname
$USER->email
$codeTAC
*/
$arr=array('displayname' => $USER->displayname, 'email' => $USER->email,'codeTAC' => $codeTAC);
$codeJSON = json_encode($arr);

//$codeJSON_CRYP = cryto($codeJSON);
$codeJSON_CRYP = base64_encode($codeJSON);

//Fonctions de décodage json et base64 : https://www.php.net/manual/fr/function.json-decode.php et https://www.php.net/manual/fr/function.base64-decode.php

// ******************** Render view **************************
$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav($menu);

if($debug){echo "Le code JSON est : ".$codeJSON."<br>";}
if($debug){echo "encrytage du JSON  : ".$codeJSON_CRYP."<br>";}
echo (__("Click on the button below to copy the code to paste into the Topaze application")."<br>");
echo "<input type='text' value='".$codeJSON_CRYP."' id='myInput'  disabled='disabled'>"; //Le champ texte à afficher
$text_button = __('Copy text');
echo "<button onclick='myFunction()' id='myButton'>".$text_button."</button>";//Le bouton pour copier le texte

//**************Instructor Affichage du formulaire de réglages **************/
if ( $USER->instructor ) {
    echo('<div style="float:right;">');
    echo('<form method="post" style="display: inline">');
    echo('<input type="submit" class="btn btn-warning" name="clear" value="'.__('Clear data').'">');
    echo("</form>\n");
    echo('</div>');

    $OUTPUT->welcomeUserCourse();
    echo('<br clear="all">');
    SettingsForm::start();
    //echo("__('Configure the LTI Tool')\n");
    //SettingsForm::text('title',__('Configure the LTI Tool'));
    SettingsForm::text('code',__('Topaze Application Code'));
    SettingsForm::text('url',__('Topaze Application URL'));
//    SettingsForm::checkbox('grade',__('Send a grade'));
 //   SettingsForm::text('match',__('This can be a prefix of an IP address like "142.16.41" or if it starts with a "/" it can be a regular expression (PHP syntax)'));
  //  echo("<p>Your current IP address is ".htmlentities(Net::getIP())."</p>\n");
    SettingsForm::done();
    SettingsForm::end();
}
//**************Instructor **************/


$OUTPUT->flashMessages();



// Ask the user for the code

if ( $USER->instructor ) {
    echo("<p>");
    if ( strlen($old_code) < 1 ) {
        echo(__("Use the settings link to configure the attendance code."));
    } else {
        echo(__("You can use the settings link to change the attendance code."));
    }
    echo("</p>\n");
} else { 
    //*************** Student *******************************
    //print_r($RESULT);
    echo(__("Hello ").$USER->displayname."<br>");
    echo(__("Your grade is : ").$RESULT->grade."<br>");//AJOUT ED 2020
    echo(__("Topaze Application URL : ")."<a href='".$urlTAU."'>".__("click to access your application")."</a>"."<br>" );
    echo('<form method="post">');
    echo(_("Enter coded grade :")."\n");
   echo('<input type="text" name="grade" value=""> '); //AJOUT ED 2020
    echo('<input type="submit" class="btn btn-normal" name="set" value="'.__('Record your code').'"><br/>');
    echo("\n</form>\n");
}

// Check the regex
if ( $USER->instructor && strlen($match) > 0 && substr($match, 0, 1) == '/' ) {
   @preg_match($match, $ip);
   if ( preg_last_error() != PREG_NO_ERROR ) {
       echo('<p style="color:red;">Syntax error in regular expression '.htmlentities($match)."</p>\n");
    }
}

if ( $rows ) {
    echo('<table border="1">'."\n");
    echo("<tr><th>"._("User")."</th><th>"._("Attendance")."</th><th>"._("IP Address")."</th></tr>\n");
    foreach ( $rows as $row ) {
        echo "<tr><td>";
        echo('<a href="#" onclick="alert(\'');
        echo($row['user_id']);
        if ( strlen($row['email']) > 0 || strlen($row['displayname']) > 0 ) echo(' | ');
        if ( strlen($row['email']) > 0 ) {
            echo(htmlent_utf8($row['email']));
            if ( strlen($row['displayname']) > 0 ) echo(' | ');
        }
        if ( strlen($row['displayname']) > 0 ) {
            echo(htmlent_utf8($row['displayname']));
        }
        echo('\'); return false;">');
        echo(' &nbsp;*******&nbsp; ');
        echo('</a>');
        echo("</td><td>");
        echo($row['cryptopaze']);
        echo("</td><td>");
        echo(htmlent_utf8($row['ipaddr']));
        echo("</td></tr>\n");
    }
    echo("</table>\n");
}

$OUTPUT->footer();


//Fonctions supplémentaires (E.DUQUENOY 2020)
function gradeDecode($gradeCoded){
    //On suppose la note comprise entre 0 et 255.
    //Cependant, "grade" doit être compris entre 0.0 et 1.0. La fonction va donc ajuster également l'échelle
    $result=($gradeCoded & 255)/100.0;
    return($result);    
}

function cryto($texte){
    $toto = strlen($texte);
    echo "Taille : ".$toto."<br>";
    /*for($i=0 ; $i < $toto ; $i++){
        
        $tmp = charCodeAt($texte, $i) ;

        $textecryte=$textecrypte + base_convert($tmp,10,16) ;
     
    }*/
    
return(0);

}
function charCodeAt($texte, $offset) {
    $texte = substr($texte, $offset, 1);
    list(, $ret) = unpack('S', mb_convert_encoding($character, 'UTF-16LE'));
    return($ret);
    }