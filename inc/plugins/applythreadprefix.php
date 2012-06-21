<?php
/**
 * Apply Thread Prefix
 * Copyright 2011 Starpaul20
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// Tell MyBB when to run the hooks
$plugins->add_hook("moderation_start", "applythreadprefix_run");
$plugins->add_hook("showthread_start", "applythreadprefix_lang");
$plugins->add_hook("forumdisplay_start", "applythreadprefix_lang");

// The information that shows up on the plugin manager
function applythreadprefix_info()
{
	return array(
		"name"				=> "Apply Thread Prefix",
		"description"		=> "Allows moderators to apply a thread prefix to threads without having to edit the post.",
		"website"			=> "http://galaxiesrealm.com/index.php",
		"author"			=> "Starpaul20",
		"authorsite"		=> "http://galaxiesrealm.com/index.php",
		"version"			=> "1.0",
		"guid"				=> "f0438243c9bb40a6373214dc6349d889",
		"compatibility"		=> "16*"
	);
}

// This function runs when the plugin is activated.
function applythreadprefix_activate()
{
	global $db;
	$insert_array = array(
		'title'		=> 'moderation_applyprefix',
		'template'	=> $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->apply_thread_prefix}</title>
{$headerinclude}
</head>
<body>
{$header}
<form action="moderation.php" method="post">
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="2"><strong>{$lang->apply_thread_prefix}</strong></td>
</tr>
{$loginbox}
<tr>
<td class="trow1"><strong>{$lang->new_prefix}</strong><br /><span class="smalltext">{$lang->prefix_note}</span></td>
<td class="trow2">{$prefixselect}</td>
</tr>
</table>
<br />
<div align="center"><input type="submit" class="button" name="submit" value="{$lang->apply_thread_prefix}" /></div>
<input type="hidden" name="action" value="do_applyprefix" />
<input type="hidden" name="tid" value="{$tid}" />
</form>
{$footer}
</body>
</html>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	$insert_array = array(
		'title'		=> 'moderation_inline_applyprefix',
		'template'	=> $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->apply_thread_prefix}</title>
{$headerinclude}
</head>
<body>
{$header}
<form action="moderation.php" method="post">
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="2"><strong>{$lang->apply_thread_prefix}</strong></td>
</tr>
{$loginbox}
<tr>
<td class="trow1"><strong>{$lang->new_prefix}</strong><br /><span class="smalltext">{$lang->prefix_note}</span></td>
<td class="trow2">{$prefixselect}</td>
</tr>
</table>
<br />
<div align="center"><input type="submit" class="button" name="submit" value="{$lang->apply_thread_prefix}" /></div>
<input type="hidden" name="action" value="do_multiapplyprefix" />
<input type="hidden" name="fid" value="{$fid}" />
<input type="hidden" name="threads" value="{$inlineids}" />
</form>
{$footer}
</body>
</html>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("showthread_moderationoptions", "#".preg_quote('</optgroup>')."#i", '<option value="applyprefix">{$lang->apply_thread_prefix}</option></optgroup>');
	find_replace_templatesets("forumdisplay_inlinemoderation", "#".preg_quote('</optgroup>')."#i", '<option value="multiapplyprefix">{$lang->apply_thread_prefix}</option></optgroup>');
}

// This function runs when the plugin is deactivated.
function applythreadprefix_deactivate()
{
	global $db;
	$db->delete_query("templates", "title IN('moderation_applyprefix','moderation_inline_applyprefix')");

	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("showthread_moderationoptions", "#".preg_quote('<option value="applyprefix">{$lang->apply_thread_prefix}</option>')."#i", '', 0);
	find_replace_templatesets("forumdisplay_inlinemoderation", "#".preg_quote('<option value="multiapplyprefix">{$lang->apply_thread_prefix}</option>')."#i", '', 0);
}

// Apply Thread Prefix moderation page
function applythreadprefix_run()
{
	global $db, $mybb, $lang, $templates, $theme, $headerinclude, $header, $footer, $loginbox, $applyprefix, $moderation, $inlineids;
	$lang->load("applythreadprefix");

	if($mybb->input['action'] != "applyprefix" && $mybb->input['action'] != "do_applyprefix" && $mybb->input['action'] != "multiapplyprefix" && $mybb->input['action'] != "do_multiapplyprefix")
	{
		return;
	}

	if($mybb->user['uid'] != 0)
	{
		eval("\$loginbox = \"".$templates->get("changeuserbox")."\";");
	}
	else
	{
		eval("\$loginbox = \"".$templates->get("loginbox")."\";");
	}

	$tid = intval($mybb->input['tid']);
	$thread = get_thread($tid);

	$fid = intval($mybb->input['fid']);
	$forum = get_forum($fid);

	if($mybb->input['action'] == "applyprefix" && $mybb->request_method == "post")
	{
		// Verify incoming POST request
		verify_post_check($mybb->input['my_post_key']);

		if(!is_moderator($thread['fid'], "canmanagethreads"))
		{
			error_no_permission();
		}

		if(!$thread['tid'])
		{
			error($lang->error_invalidthread);
		}

		check_forum_password($thread['fid']);

		$thread['subject'] = htmlspecialchars_uni($thread['subject']); 

		build_forum_breadcrumb($thread['fid']);
		add_breadcrumb($thread['subject'], get_thread_link($thread['tid']));
		add_breadcrumb($lang->nav_apply_prefix);

		$prefixselect = build_prefix_select($thread['fid'], $mybb->input['threadprefix']);

		eval("\$applyprefix = \"".$templates->get("moderation_applyprefix")."\";");
		output_page($applyprefix);
	}

	if($mybb->input['action'] == "do_applyprefix" && $mybb->request_method == "post")
	{
		// Verify incoming POST request
		verify_post_check($mybb->input['my_post_key']);

		if(!is_moderator($thread['fid'], "canmanagethreads"))
		{
			error_no_permission();
		}

		if(!intval($mybb->input['threadprefix']))
		{
			error($lang->no_prefix_selected);
		}

		$moderation->apply_thread_prefix($thread['tid'], $mybb->input['threadprefix']);

		log_moderator_action(array("tid" => $thread['tid'], "fid" => $thread['fid']), $lang->thread_prefix_applied);

		moderation_redirect(get_thread_link($thread['tid']), $lang->redirect_thread_prefix_applied);
	}

	if($mybb->input['action'] == "multiapplyprefix" && $mybb->request_method == "post")
	{
		// Verify incoming POST request
		verify_post_check($mybb->input['my_post_key']);

		if(!is_moderator($forum['fid'], "canmanagethreads"))
		{
			error_no_permission();
		}

		if(!$forum['fid'])
		{
			error($lang->error_invalidforum);
		}

		$threads = getids($fid, 'forum');

		if(count($threads) < 1)
		{
			error($lang->error_inline_nothreadsselected);
		}

		$inlineids = implode("|", $threads);
		clearinline($fid, 'forum');

		check_forum_password($forum['fid']);

		build_forum_breadcrumb($forum['fid']);
		add_breadcrumb($lang->nav_apply_prefix);

		$prefixselect = build_prefix_select($forum['fid'], $mybb->input['threadprefix']);

		eval("\$multiapplyprefix = \"".$templates->get("moderation_inline_applyprefix")."\";");
		output_page($multiapplyprefix);
	}

	if($mybb->input['action'] == "do_multiapplyprefix" && $mybb->request_method == "post")
	{
		// Verify incoming POST request
		verify_post_check($mybb->input['my_post_key']);

		$threadlist = explode("|", $mybb->input['threads']);
		foreach($threadlist as $tid)
		{
			$tid = intval($tid);
			$tlist[] = $tid;
		}

		if(!is_moderator($forum['fid'], "canmanagethreads"))
		{
			error_no_permission();
		}

		if(!intval($mybb->input['threadprefix']))
		{
			error($lang->no_prefix_selected);
		}

		$moderation->apply_thread_prefix($tlist, $mybb->input['threadprefix']);

		log_moderator_action(array("fid" => $forum['fid']), $lang->thread_prefix_applied);

		moderation_redirect(get_forum_link($forum['fid']), $lang->redirect_inline_thread_prefix_applied);
	}
	exit;
}

// Shows language on show thread and forum display
function applythreadprefix_lang()
{
	global $lang;
	$lang->load("applythreadprefix");
}

?>