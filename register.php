<?php
// +-----------------------------------------------------------------------+
// | This file is part of Piwigo.                                          |
// |                                                                       |
// | For copyright and license information, please view the COPYING.txt    |
// | file that was distributed with this source code.                      |
// +-----------------------------------------------------------------------+

//----------------------------------------------------------- include
define('PHPWG_ROOT_PATH','./');
include_once( PHPWG_ROOT_PATH.'include/common.inc.php' );

// +-----------------------------------------------------------------------+
// | Check Access and exit when user status is not ok                      |
// +-----------------------------------------------------------------------+
check_status(ACCESS_FREE);

//----------------------------------------------------------- user registration

if (!$conf['allow_user_registration'])
{
  page_forbidden('User registration closed');
}

trigger_notify('loc_begin_register');

if (isset($_POST['submit']))
{
  if (!verify_ephemeral_key(@$_POST['key']))
  {
		set_status_header(403);
    $page['errors']['register_page_error'] = l10n('Invalid/expired form key');
  }

  if(empty($_POST['password']))
  {
    $page['errors']['register_form_error'] = l10n('Password is missing. Please enter the password.');
  }
  else if(empty($_POST['password_conf']))
  {
    $page['errors']['register_form_error'] = l10n('Password confirmation is missing. Please confirm the chosen password.');
  }
  else if ($_POST['password'] != $_POST['password_conf'])
  {
    $page['errors']['register_form_error'] = l10n('The passwords do not match');
  }

  register_user(
    $_POST['login'],
    $_POST['password'],
    $_POST['mail_address'],
    true,
    $page['errors'],
    isset($_POST['send_password_by_mail'])
    );

  if (count($page['errors']) == 0)
  {
    // email notification
    if (isset($_POST['send_password_by_mail']) and email_check_format($_POST['mail_address']))
    {
      $_SESSION['page_infos'][] = l10n('Successfully registered, you will soon receive an email with your connection settings. Welcome!');
    }
    
    // log user and redirect
    $user_id = get_userid($_POST['login']);
    log_user($user_id, false);
    redirect(make_index_url());
  }
	$registration_post_key = get_ephemeral_key(2);
}
else
{
	$registration_post_key = get_ephemeral_key(6);
}

$login = !empty($_POST['login'])?htmlspecialchars(stripslashes($_POST['login'])):'';
$email = !empty($_POST['mail_address'])?htmlspecialchars(stripslashes($_POST['mail_address'])):'';

//----------------------------------------------------- template initialization
//
// Start output of page
//
$title= l10n('Registration');
$page['body_id'] = 'theRegisterPage';

$template->set_filenames( array('register'=>'register.tpl') );
$template->assign(array(
  'U_HOME' => make_index_url(),
	'F_KEY' => $registration_post_key,
  'F_ACTION' => 'register.php',
  'F_LOGIN' => $login,
  'F_EMAIL' => $email,
  'obligatory_user_mail_address' => $conf['obligatory_user_mail_address'],
));

// include menubar
$themeconf = $template->get_template_vars('themeconf');
if (!isset($themeconf['hide_menu_on']) OR !in_array('theRegisterPage', $themeconf['hide_menu_on']))
{
  include( PHPWG_ROOT_PATH.'include/menubar.inc.php');
}

//Load language if cookie is set from login/register/password pages
if (isset($_COOKIE['lang']) and $user['language'] != $_COOKIE['lang'])
{
  if (!array_key_exists($_COOKIE['lang'], get_languages()))
  {
    fatal_error('[Hacking attempt] the input parameter "'.$_COOKIE['lang'].'" is not valid');
  }
  
  $user['language'] = $_COOKIE['lang'];
  load_language('common.lang', '', array('language'=>$user['language']));
}

//Get list of languages
foreach (get_languages() as $language_code => $language_name)
{
  $language_options[$language_code] = $language_name;
}

$template->assign(array(
  'language_options' => $language_options,
  'current_language' => $user['language'],
));

//Get link to doc
if ('fr' == substr($user['language'], 0, 2))
{
  $help_link = "https://doc-fr.piwigo.org/les-utilisateurs/se-connecter-a-piwigo";
}
else
{
  $help_link = "https://doc.piwigo.org/managing-users/log-in-to-piwigo";
}

$template->assign('HELP_LINK', $help_link);

include(PHPWG_ROOT_PATH.'include/page_header.php');
trigger_notify('loc_end_register');
flush_page_messages();
$template->parse('register');
include(PHPWG_ROOT_PATH.'include/page_tail.php');
?>
