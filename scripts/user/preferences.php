<?php
/**
 *  
 **/

$GENDER = array('male' => 'Male', 'female' => 'Female');
$AGE = array();
for($i=5;$i<70;$i++)
{
    $AGE[$i] = $i;
}
$EDITORS = array('tinymce' => 'Rich Text Editor','plain' => 'Plain Textbox');

switch($_REQUEST['state'])
{
    default:
    {
        $PREFS = array(
            'gender' => $User->getGender(),
            'age' => $User->getAge(),
            'email' => $User->getEmail(),
            'editor' => $User->getTextareaPreference(),
            'profile' => $User->getProfile(),
            'signature' => $User->getSignature(),
        );

        // If someone hit here with defaults from the back button,
        // give those values precedence.
        if(is_array($_POST['default']) == true)
        {
            foreach($_POST['default'] as $key => $value)
            {
                // Stripinput on normal fields, xhtml cleaner for the rest.
                if(in_array($key,array('profile','signature')) == false)
                {
                    $PREFS[$key] = stripinput($value);
                }
                else
                {
                    $PREFS[$key] = clean_xhtml($value);
                }
            } // end mog-into-prefs loop
        } // end defaults
        
        if($_SESSION['pref_notice'] != null)
        {
            $renderer->assign('notice',$_SESSION['pref_notice']);
            unset($_SESSION['pref_notice']);
        }

        $renderer->assign('genders',$GENDER);
        $renderer->assign('ages',$AGE);
        $renderer->assign('editors',$EDITORS);
        $renderer->assign('prefs',$PREFS);
        $renderer->display('user/preferences/preferences.tpl');

        break;
    } // end default

    case 'save':
    {
        $ERRORS = array();
        $reset_login = false; // True = refresh cookie just before redirect(). 
        
        $PASSWORD = array(
            'old' => $_POST['password']['old'],
            'new' => $_POST['password']['a'],
            'new_confirm' => $_POST['password']['b'],
        );
        
        $USER = array(
            'email' => stripinput($_POST['user']['email']),
            'gender' => stripinput($_POST['user']['gender']),
            'age' => stripinput($_POST['user']['age']),
            'editor' => stripinput($_POST['user']['editor']),
            'profile' => clean_xhtml($_POST['user']['profile']),
            'signature' => clean_xhtml($_POST['user']['signature']),
        );
       
        if($PASSWORD['new_confirm'] != null || $PASSWORD['new_confirm'])
        {
            if(md5(md5($PASSWORD['old'])) != $User->getPasswordHash())
            {
                $ERRORS[] = 'The old password you specified is incorrect.';
            }
            else
            {
                if($PASSWORD['new'] != $PASSWORD['new_confirm'])
                {
                    $ERRORS[] = 'The two new passwords you specified did not match.';
                }
                else
                {
                    // Update this here and don't worry about it later.
                    $User->setPassword($PASSWORD['new']);
                    $reset_login = true;
                } // update PW
            } // end old pw is OK
            
        } // end password change

        if($USER['email'] != $User->getEmail())
        {
            if(md5(md5($PASSWORD['old'])) != $User->getPasswordHash())
            {
                $ERRORS[] = 'The old password you specified is incorrect.';
            }

            if(preg_match('/^[a-z0-9_+.]{1,64}@([a-z0-9-.]*){1,}\.[a-z]{1,5}$/i',$USER['email']) == false)
            {
                $ERRORS[] = 'Invalid e-mail address specified.';
            }
        } // end do email update
        
        if(in_array($USER['gender'],array_keys($GENDER)) == false)
        {
            $ERRORS[] = 'Invalid gender specified.';
        }

        if(in_array($USER['age'],$AGE) == false)
        {
            $ERRORS[] = 'Invalid age specified.';
        }

        if(in_array($USER['editor'],array_keys($EDITORS)) == false)
        {
            $ERRORS[] = 'Invalid editor specified.';
        }
        
        if(sizeof($ERRORS) > 0)
        {
            draw_errors($ERRORS);

            $renderer->assign('info',$USER);
            $renderer->display('user/preferences/pref_errors_back.tpl');
        }
        else
        {
            // === Note:
            // User#setPassword() handled above - see there for details.
            
            $User->setEmail($USER['email']);
            $User->setAge($USER['age']);
            $User->setGender($USER['gender']);
            $User->setProfile($USER['profile']);
            $User->setSignature($USER['signature']);
            $User->setTextareaPreference($USER['editor']); 
            $User->save();
            
            // Update the password hash stored in the cookie.
            // Otherwise, they'd be kicked out upon redirect.
            if($reset_login == true)
            {
                $User->logout();
                $User->login();
            } // end reset login

            $_SESSION['pref_notice'] = 'Your preferences have been updated.';
            redirect('preferences');
        } // end do save
        
        break; 
    } // end save
} // end switch
?>