## Introduction
  This is a phpBB3 MOD for myGengo.
   
## Installation
  1. Copy the contents of the "phpBB3" folder into your phpBB installation folder.
  2. Point your web brower to DOMAIN.TLD/PHPBB_DIR/install_mygengo.php.
  3. Invalidate your phpBB caches, either by:
    1. Deleting all the files in the /PHPBB_DIR/cache/ subfolder via FTP or SSH.
    2. Running the "Purge the cache" action in the phpBB Administrator Control Panel.
  4. Go to the phpBB Administrator Control Panel ("General" tab) and set up your API keys by clicking on any link under the "myGengo".

## HOWTO: Add myGengo Button to every Forum Post (Overwrite Files)

For your convenience, I've provided the modified files (based on phpBB 3.0.8) for the themes "prosilver" and "subsilver2".
You should only overwrite these files if you're sure your phpBB version is compatible with the 3.0.8 and if you haven't manually changed any of the files.

  1. Copy the contents of the "phpBB3_themes_mygengo" folder into your phpBB installation folder.
  2. Invalidate your phpBB caches (see above).
  3. All done, you should see a small myGengo icon next to each forum post.

## HOWTO: Add myGengo Button to every Forum Post (DIY Instructions)
  1. Open the file PHPBB_DIR/viewtopic.php.
  2. Locate the line with the following code: ```$template->assign_block_vars('postrow', $postrow);```.
  3. Add the following code **immediately before** that line:

	$postrow['U_MYGENGO'] = $auth->acl_get('a_') ? append_sid($phpbb_root_path . 'adm/index.' . $phpEx, 'i=mygengo&mode=order&post_id=' . $row['post_id']) : '';

  4. Open the file PHPBB_DIR/YOUR_STYLE/template/viewtopic_body.html.
  5. Locate the line that starts with the following code: ```<!-- IF postrow.U_EDIT -->```.
  6. Add the following code **immediately before** that line:

	<!-- IF postrow.U_MYGENGO --><li class="mygengo-icon"><a href="{postrow.U_MYGENGO}"><img src="./styles/icon_mygengo.gif" alt="" /></a></li><!-- ENDIF -->

  7. Invalidate your phpBB caches (see above).
  8. All done, you should see a small myGengo icon next to each forum post.

## Author and License
  Copyright (c) 2011 Alix Axel (http://opensource.org/licenses/lgpl-3.0.html)