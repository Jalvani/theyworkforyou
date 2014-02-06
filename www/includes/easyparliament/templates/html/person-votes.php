<?php

global $NEWPAGE;
# Some big IFs that are used multiple times. Methods on a class instance, you say, what's that?
$member['has_voting_record'] = ((in_array(HOUSE_TYPE_COMMONS, $member['houses']) && $member['party'] != 'Sinn Fein') || in_array(HOUSE_TYPE_LORDS, $member['houses']));
$member['has_recent_appearances'] = !(in_array(HOUSE_TYPE_COMMONS, $member['houses']) && $member['party'] == 'Sinn Fein' && !in_array(HOUSE_TYPE_NI, $member['houses']));
$member['has_email_alerts'] = $member['current_member'][HOUSE_TYPE_ROYAL] || $member['current_member'][HOUSE_TYPE_LORDS] || $member['current_member'][HOUSE_TYPE_NI] || ($member['current_member'][HOUSE_TYPE_COMMONS] && $member['party'] != 'Sinn Fein') || $member['current_member'][HOUSE_TYPE_SCOTLAND];
$member['has_video_matching'] = $member['current_member'][HOUSE_TYPE_COMMONS] && $member['party'] != 'Sinn Fein';
$member['has_expenses'] = isset($extra_info['expenses2004_col1']) || isset($extra_info['expenses2006_col1']) || isset($extra_info['expenses2007_col1']) || isset($extra_info['expenses2008_col1']);

# Heading/ picture
$mp_panel_columns = 'large-12'; // columns for main info panel
$button_columns = '';           // columns for alert/wtt buttons
if ($member['has_email_alerts']) {
    $mp_panel_columns = 'large-9';
}

?>
            <p class="printonly">This data was produced by TheyWorkForYou from a variety of sources.</p>
            <a data-magellan-destination="profile" name="profile"></a>
            <div class="panel-row" id="mp-panel">
                <div class="<?=$mp_panel_columns?> small-12 columns" id="mp-details">
                    <?php person_image($member); ?>

                    <div class="mp-details">
                        <h1><?=$member['full_name']?></h1>
                        <h2>Voting Record</h3>
                        <h3 class="subheader"><?php print person_summary_description($member) ?></h3>

<?php
//if dummy image, show message asking for a photo
if (!exists_rep_image($member['person_id'])) {
    person_ask_for_picture($member);
}

?>
                        <ul class="hilites clear">
<?php
print "                        ";
person_enter_leave_facts($member, $extra_info);
person_majority($extra_info);
get_member_history($member, $extra_info);
print "\n";

if ($member['party'] == 'Sinn Fein' && in_array(HOUSE_TYPE_COMMONS, $member['houses'])) {
    print '<li>Sinn F&eacute;in MPs do not take their seats in Parliament</li>';
}
?>
                        </ul>
                    </div> <!-- end .mp-details -->
                </div> <!-- end #mp-details -->

                <div class="small-12 large-3 person-button-column">
                    <a class="button alert person-contact-button" href="<?= $member['profile_url'] ?>"><strong>View <?=$member['full_name']?>&rsquo;s Full Profile</strong></a>
                </div>
            </div> <!-- end mp panel -->

            <div data-magellan-expedition="fixed" class="person-subnav">
                <dl class="sub-nav">
                    <dt data-magellan-arrival="profile"><a href="#profile">Profile</a></dt>
                    <dt data-magellan-arrival="votingrecord"><a href="#votingrecord">Voting<span class="hide-for-small"> record</span></a></dt>
                    <dt data-magellan-arrival="hansard"><a href="#hansard">Appearances</a></dt>
                    <dt data-magellan-arrival="register"><a href="#register">Register<span class="hide-for-small"> of interests</span></a></dt>
                </dl>
            </div>

            <div class="row">
                <div class="large-8 columns" id="main-content">
<?php

/*
person_user_actions($member);
person_internal_links($member, $extra_info);
 */

if ($member['has_voting_record']) {
    $NEWPAGE->panel_start('votingrecord', true);
    person_voting_record($member, $extra_info);
    $NEWPAGE->panel_end();
}

$member['chairmens_panel'] = false;


# Topics of interest only for current MPs at the moment
if ($member['current_member'][HOUSE_TYPE_COMMONS]) {
    // we used to generate this when we were checking topics but this
    // is now in the sidebar so we don't check this till too late
    if ( array_key_exists('office', $extra_info) ) {
        foreach ($extra_info['office'] as $row) {
            if ($row['dept'] == "Chairmen's Panel Committee") {
                $member['chairmens_panel'] = true;
            }
        }
    }
}

# Helper functions

# Gets and outputs the correct image (with special case for Lords)
function person_image($member) {
	$is_lord = in_array(HOUSE_TYPE_LORDS, $member['houses']);
	if ($is_lord) {
		list($image,$sz) = find_rep_image($member['person_id'], false, 'lord');
	} else {
		list($image,$sz) = find_rep_image($member['person_id'], false, true);
	}
	echo '<p class="person">';
	echo '<img alt="Photo of ', $member['full_name'], '" src="', $image, '"';
	if ($sz=='S') echo ' height="118"';
	echo '></p>';
}

# Given a MEMBER array, return a summary description of the person's member positions,
# e.g. "Former MLA for Toytown, MP for Trumpton"
function person_summary_description($member) {
	if (in_array(HOUSE_TYPE_ROYAL, $member['houses'])) { # Royal short-circuit
		return '<li><strong>Acceded on ' . $member['entered_house'][HOUSE_TYPE_ROYAL]['date_pretty']
			. '</strong></li><li><strong>Coronated on 2 June 1953</strong></li>';
	}
	$desc = '';
	foreach ($member['houses'] as $house) {
		if ($house==HOUSE_TYPE_COMMONS && isset($member['entered_house'][HOUSE_TYPE_LORDS]))
			continue; # Same info is printed further down

		if (!$member['current_member'][$house]) $desc .= 'Former ';

		$party = $member['left_house'][$house]['party'];
		$party_br = '';
		if (preg_match('#^(.*?)\s*\((.*?)\)$#', $party, $m)) {
			$party_br = $m[2];
			$party = $m[1];
		}
		if ($party != 'unknown')
			$desc .= htmlentities($party);
		if ($party == 'Speaker' || $party == 'Deputy Speaker') {
			$desc .= ', and ';
			# XXX: Might go horribly wrong if something odd happens
			if ($party == 'Deputy Speaker') {
				$last = end($member['other_parties']);
				$desc .= $last['from'] . ' ';
			}
		}
		if ($house==HOUSE_TYPE_COMMONS || $house==HOUSE_TYPE_NI || $house==HOUSE_TYPE_SCOTLAND) {
			$desc .= ' ';
			if ($house==HOUSE_TYPE_COMMONS) $desc .= '<abbr title="Member of Parliament">MP</abbr>';
			if ($house==HOUSE_TYPE_NI) $desc .= '<abbr title="Member of the Legislative Assembly">MLA</abbr>';
			if ($house==HOUSE_TYPE_SCOTLAND) $desc .= '<abbr title="Member of the Scottish Parliament">MSP</abbr>';
			if ($party_br) {
				$desc .= " ($party_br)";
			}
			$desc .= ' for ' . $member['left_house'][$house]['constituency'];
		}
		if ($house==HOUSE_TYPE_LORDS && $party != 'Bishop') $desc .= ' Peer';
		$desc .= ', ';
	}
	$desc = preg_replace('#, $#', '', $desc);
	return $desc;
}

function get_member_history($member, $extra_info) {
    $history = '';
    if ($member['other_constituencies']) {
        $history .=  "<li>Also represented " . join('; ', array_keys($member['other_constituencies']));
        $history .= '</li>';
    }

    if ($member['other_parties'] && $member['party'] != 'Speaker' && $member['party'] != 'Deputy Speaker') {
        $history .= "<li>Changed party ";
        foreach ($member['other_parties'] as $r) {
            $out[] = 'from ' . $r['from'] . ' on ' . format_date($r['date'], SHORTDATEFORMAT);
        }
        $history .= join('; ', $out);
        $history .= '</li>';
    }

    // Ministerial positions
    if (array_key_exists('office', $extra_info)) {
        person_offices($extra_info);
    }

    if (exists_rep_image($member['person_id']) && isset($extra_info['photo_attribution_text']) && $extra_info['photo_attribution_text']) {
        $history .= '<li><small>Photo: ';
        if (isset($extra_info['photo_attribution_link']) && $extra_info['photo_attribution_link']) {
            $history .= '<a href="' . $extra_info['photo_attribution_link'] . '" rel="nofollow">';
        }
        $history .= $extra_info['photo_attribution_text'];
        if (isset($extra_info['photo_attribution_link']) && $extra_info['photo_attribution_link']) {
            $history .= '</a>';
        }
        $history .= '</small></li>';
    }
}

function person_offices($extra_info) {
	$mins = array();
	foreach ($extra_info['office'] as $row) {
		if ($row['to_date'] == '9999-12-31' && $row['source'] != 'chgpages/selctee' && $row['source'] != 'datadotparl/committee' ) {
			$m = prettify_office($row['position'], $row['dept']);
			$m .= ' (since ' . format_date($row['from_date'], SHORTDATEFORMAT) . ')';
			$mins[] = $m;
		}
	}
	if ($mins) {
		print '<li>' . join('<br>', $mins) . '</li>';
	}
}

function person_ask_for_picture($member) {
	echo '<p class="missingphoto">
We&rsquo;re missing a photo of ' .  $member['full_name'] . '. If you have a
photo <em>that you can release under a Creative Commons Attribution-ShareAlike
license</em> or can locate a <em>copyright free</em> photo,
<a href="mailto:' . str_replace('@', '&#64;', CONTACTEMAIL) . '">please email it to us</a>. Please do not
email us about copyrighted photos elsewhere on the internet; we can&rsquo;t use
them.</p>';
}

function person_enter_leave_facts($member, $extra_info) {
	if (isset($member['left_house'][HOUSE_TYPE_COMMONS]) && isset($member['entered_house'][HOUSE_TYPE_LORDS])) {
		print '<li>Entered the House of Lords ';
		if (strlen($member['entered_house'][HOUSE_TYPE_LORDS]['date_pretty'])==4)
			print 'in ';
		else
			print 'on ';
		print $member['entered_house'][HOUSE_TYPE_LORDS]['date_pretty'].'';
		print '';
		if ($member['entered_house'][HOUSE_TYPE_LORDS]['reason']) print ' &mdash; ' . $member['entered_house'][HOUSE_TYPE_LORDS]['reason'];
		print '</li>';
		print '<li>Previously MP for ';
		print $member['left_house'][HOUSE_TYPE_COMMONS]['constituency'] . ' until ';
		print $member['left_house'][HOUSE_TYPE_COMMONS]['date_pretty'].'';
		if ($member['left_house'][HOUSE_TYPE_COMMONS]['reason']) print ' &mdash; ' . $member['left_house'][HOUSE_TYPE_COMMONS]['reason'];
		print '</li>';
	} elseif (isset($member['entered_house'][HOUSE_TYPE_LORDS]['date'])) {
		print '<li>Became a Lord ';
		if (strlen($member['entered_house'][HOUSE_TYPE_LORDS]['date_pretty'])==4)
			print 'in ';
		else
			print 'on ';
		print $member['entered_house'][HOUSE_TYPE_LORDS]['date_pretty'].'';
		if ($member['entered_house'][HOUSE_TYPE_LORDS]['reason']) print ' &mdash; ' . $member['entered_house'][HOUSE_TYPE_LORDS]['reason'];
		print '</li>';
	}
	if (in_array(HOUSE_TYPE_LORDS, $member['houses']) && !$member['current_member'][HOUSE_TYPE_LORDS]) {
		print '<li>Left Parliament on '.$member['left_house'][HOUSE_TYPE_LORDS]['date_pretty'].'';
		if ($member['left_house'][HOUSE_TYPE_LORDS]['reason']) print ' &mdash; ' . $member['left_house'][HOUSE_TYPE_LORDS]['reason'];
		print '</li>';
	}

	if (isset($extra_info['lordbio'])) {
		echo '<li>Positions held at time of appointment: ', $extra_info['lordbio'],
			' <small>(from <a href="',
			$extra_info['lordbio_from'], '">Number 10 press release</a>)</small></li>';
	}

	if (isset($member['entered_house'][HOUSE_TYPE_COMMONS]['date'])) {
		print '<li>Entered Parliament on ';
		print $member['entered_house'][HOUSE_TYPE_COMMONS]['date_pretty'].'';
		if ($member['entered_house'][HOUSE_TYPE_COMMONS]['reason']) print ' &mdash; ' . $member['entered_house'][HOUSE_TYPE_COMMONS]['reason'];
		print '</li>';
	}
	if (in_array(HOUSE_TYPE_COMMONS, $member['houses']) && !$member['current_member'][HOUSE_TYPE_COMMONS] && !isset($member['entered_house'][HOUSE_TYPE_LORDS])) {
		print '<li>Left Parliament ';
		if (strlen($member['left_house'][HOUSE_TYPE_COMMONS]['date_pretty'])==4)
			print 'in ';
		else
			print 'on ';
		echo $member['left_house'][HOUSE_TYPE_COMMONS]['date_pretty'].'';
		if ($member['left_house'][HOUSE_TYPE_COMMONS]['reason']) print ' &mdash; ' . $member['left_house'][HOUSE_TYPE_COMMONS]['reason'];
		print '</li>';
	}

	if (isset($member['entered_house'][HOUSE_TYPE_NI]['date'])) {
		print '<li>Entered the Assembly on ';
		print $member['entered_house'][HOUSE_TYPE_NI]['date_pretty'].'';
		if ($member['entered_house'][HOUSE_TYPE_NI]['reason']) print ' &mdash; ' . $member['entered_house'][HOUSE_TYPE_NI]['reason'];
		print '</li>';
	}
	if (in_array(HOUSE_TYPE_NI, $member['houses']) && !$member['current_member'][HOUSE_TYPE_NI]) {
		print '<li>Left the Assembly on '.$member['left_house'][HOUSE_TYPE_NI]['date_pretty'].'';
		if ($member['left_house'][HOUSE_TYPE_NI]['reason']) print ' &mdash; ' . $member['left_house'][HOUSE_TYPE_NI]['reason'];
		print '</li>';
	}
	if (isset($member['entered_house'][HOUSE_TYPE_SCOTLAND]['date'])) {
		print '<li>Entered the Scottish Parliament on ';
		print $member['entered_house'][HOUSE_TYPE_SCOTLAND]['date_pretty'].'';
		if ($member['entered_house'][HOUSE_TYPE_SCOTLAND]['reason']) print ' &mdash; ' . $member['entered_house'][HOUSE_TYPE_SCOTLAND]['reason'];
		print '</li>';
	}
	if (in_array(HOUSE_TYPE_SCOTLAND, $member['houses']) && !$member['current_member'][HOUSE_TYPE_SCOTLAND]) {
		print '<li>Left the Scottish Parliament on '.$member['left_house'][HOUSE_TYPE_SCOTLAND]['date_pretty'].'';
		if ($member['left_house'][HOUSE_TYPE_SCOTLAND]['reason']) print ' &mdash; ' . $member['left_house'][HOUSE_TYPE_SCOTLAND]['reason'];
		print '</li>';
	}
}

function person_majority($extra_info) {
	if (!isset($extra_info['majority_in_seat'])) return;
	print '<li>Majority: ' . number_format($extra_info['majority_in_seat']) . ' votes. ';
	if (isset($extra_info['swing_to_lose_seat_today'])) {
	/*
	if (isset($extra_info['swing_to_lose_seat_today_quintile'])) {
		$q = $extra_info['swing_to_lose_seat_today_quintile'];
		if ($q == 0) {
			print 'Very safe seat';
		} elseif ($q == 1) {
			print 'Safe seat';
		} elseif ($q == 2) {
			print '';
		} elseif ($q == 3) {
			print 'Unsafe seat';
		} elseif ($q == 4) {
			print 'Very unsafe seat';
		} else {
			print '[Impossible quintile!]';
		}
	}
	*/
		print '&mdash; ' . make_ranking($extra_info['swing_to_lose_seat_today_rank'])
			. ' out of ' . $extra_info['swing_to_lose_seat_today_rank_outof'] . ' MPs.';
	}
	echo '</li>';
}

function person_internal_links($member, $extra_info) {
	echo '<ul class="jumpers hilites">';
	# If a non-SF MP, or a Lord
	if ($member['has_voting_record']) {
		echo '<li><a href="#votingrecord">Voting record</a></li>';
		if ($member['current_member'][HOUSE_TYPE_COMMONS])
			echo '<li><a href="#topics">Topics of interest</a></li>';
	}
	# Show recent appearances, unless a SF MP who's not an MLA
	if ($member['has_recent_appearances']) {
		echo '<li><a href="#hansard">Most recent appearances</a></li>';
	}
	echo '<li><a href="#numbers">Numerology</a></li>';
	if (isset($extra_info['register_member_interests_html'])) {
		echo '<li><a href="#register">Register of Members&rsquo; Interests</a></li>';
	}

	if ($member['has_expenses']) {
		echo '<li><a href="#expenses">Expenses</a></li>';
	}
	echo '</ul>';
}

function display_dream_comparison($extra_info, $member, $dreamid, $desc, $inverse=false) {
    $out = '';
	if (isset($extra_info["public_whip_dreammp${dreamid}_distance"])) {
		if ($extra_info["public_whip_dreammp${dreamid}_both_voted"] == 0) {
			$dmpdesc = 'Has <strong>never voted</strong> on';
		} else {
			$dmpscore = floatval($extra_info["public_whip_dreammp${dreamid}_distance"]);
			$out .= "<!-- distance $dreamid: $dmpscore -->";
			if ($inverse)
				$dmpscore = 1.0 - $dmpscore;
			$english = score_to_strongly($dmpscore);
            # XXX Note special casing of 2nd tuition fee policy here
			if ($extra_info["public_whip_dreammp${dreamid}_both_voted"] == 1 || $dreamid == 1132) {
				$english = preg_replace('#(very )?(strongly|moderately) #', '', $english);
			}
			$dmpdesc = 'Voted <strong>' . $english . '</strong>';

			// How many votes Dream MP and MP both voted (and didn't abstain) in
			// $extra_info["public_whip_dreammp${dreamid}_both_voted"];
		}
        $out .= "<li>$dmpdesc $desc.
<small class='votepolicylink unneededprintlinks'>
<a href='http://www.publicwhip.org.uk/mp.php?mpid=$member[member_id]&amp;dmp=$dreamid'>Votes</a>
</small>
		</li>";
	}
	return $out;
}

# Display the person's voting record on various issues.
function person_voting_record($member, $extra_info) {
	//$this->block_start(array('id'=>'votingrecord', 'title'=>'Voting record (from PublicWhip)'));
  print '<div class="moreinfo"><span class="moreinfo-text">Read about how the voting record is decided.</span><a class="moreinfo-link" href="' . WEBPATH . 'help/#votingrecord"><img src="/images/questionmark.png" alt="" title=""></a></div>';
	print '<h2>Voting record <small>from PublicWhip</small></h2>';
	$displayed_stuff = 0;

  $member_has_died = 0;
  if (
        $member['left_house'] && (
            ( in_array(HOUSE_TYPE_COMMONS, $member['left_house']) && $member['left_house'][HOUSE_TYPE_COMMONS]['reason'] && $member['left_house'][HOUSE_TYPE_COMMONS]['reason'] == 'Died' ) ||
            ( in_array(HOUSE_TYPE_LORDS, $member['left_house']) && $member['left_house'][HOUSE_TYPE_LORDS ]['reason'] && $member['left_house'][HOUSE_TYPE_LORDS]['reason'] == 'Died' ) ||
            ( in_array(HOUSE_TYPE_SCOTLAND, $member['left_house']) && $member['left_house'][HOUSE_TYPE_SCOTLAND ]['reason'] && $member['left_house'][HOUSE_TYPE_SCOTLAND]['reason'] == 'Died' ) ||
            ( in_array(HOUSE_TYPE_NI, $member['left_house']) && $member['left_house'][HOUSE_TYPE_NI ]['reason'] && $member['left_house'][HOUSE_TYPE_NI]['reason'] == 'Died' )
        )
  ) {
      $member_has_died = 1;
  }

	if ($member['party']=='Speaker' || $member['party']=='Deputy Speaker') {
		if ($member['party']=='Speaker') $art = 'the'; else $art = 'a';
		echo "<p>As $art $member[party], $member[full_name] cannot vote (except to break a tie).</p>";
	}

	// Rebellion rate
	if (isset($extra_info['public_whip_rebellions']) && $extra_info['public_whip_rebellions'] != 'n/a') {
		$displayed_stuff = 1;
    $rebels_term = 'rebels';
    if ( $member_has_died ) {
        $rebels_term = 'rebelled';
    }
?>					<ul class="no-bullet">
						<li><a href="http://www.publicwhip.org.uk/mp.php?id=uk.org.publicwhip/member/<?=$member['member_id'] ?>#divisions" title="See more details at Public Whip">
                        <strong><?php echo htmlentities(ucfirst($extra_info['public_whip_rebel_description'])) . ' ' . $rebels_term; ?></strong></a> against their party<?php
		if (isset($extra_info['public_whip_rebelrank'])) {
			if ($member['house_disp'] == HOUSE_TYPE_LORDS) {
				echo '';
			} elseif ($extra_info['public_whip_data_date'] == 'complete') {
				echo " in their last parliament";
			} else {
			    echo " in this parliament";
			}
			/* &#8212; ";
			if (isset($extra_info['public_whip_rebelrank_joint']))
				print 'joint ';
			echo make_ranking($extra_info['public_whip_rebelrank']);
			echo " most rebellious of ";
			echo $extra_info['public_whip_rebelrank_outof'];
			echo ($member['house']=='House of Commons') ? " MPs" : ' Lords';
			*/
		}
		?>.
		</li>
	</ul><?php
	}

    require_once(INCLUDESPATH . 'policies.php');

    $policies_object = new TheyWorkForYou\Policies;

    $policies = $policies_object->shuffle()->policies;
    $joined = $policies_object->joined;

	$got_dream = '';
    foreach ($policies as $policy) {
        if (isset($policy[2]) && $policy[2] && !in_array(HOUSE_TYPE_COMMONS, $member['houses']))
            continue;
	    $got_dream .= display_dream_comparison($extra_info, $member, $policy[0], $policy[1]);
        if (isset($joined[$policy[0]])) {
		    $policy = $joined[$policy[0]];
	        $got_dream .= display_dream_comparison($extra_info, $member, $policy[0], $policy[1]);
        }
    }

	if ($got_dream) {
		$displayed_stuff = 1;
        if (in_array(HOUSE_TYPE_COMMONS, $member['houses']) && $member['entered_house'][HOUSE_TYPE_COMMONS]['date'] > '2001-06-07') {
            $since = '';
        } elseif (!in_array(HOUSE_TYPE_COMMONS, $member['houses']) && in_array(HOUSE_TYPE_LORDS, $member['houses']) && $member['entered_house'][HOUSE_TYPE_LORDS]['date'] > '2001-06-07') {
            $since = '';
        } elseif ( $member_has_died ) {
            $since = '';
        } else {
            $since = ' since 2001';
        }
        # If not current MP/Lord, but current MLA/MSP, need to say voting record is when MP
        if (!$member['current_member'][HOUSE_TYPE_COMMONS] && !$member['current_member'][HOUSE_TYPE_LORDS] && ($member['current_member'][HOUSE_TYPE_SCOTLAND] || $member['current_member'][HOUSE_TYPE_NI])) {
            $since .= ' whilst an MP';
        }
?>

<h3>How <?=$member['full_name']?> voted on key issues<?=$since?></h3>
<ul class="no-bullet" id="dreamcomparisons">
<?=$got_dream ?>
</ul>
<?
    }

	// Links to full record at Guardian and Public Whip
	$record = array();
	if (isset($extra_info['guardian_howtheyvoted'])) {
		$record[] = '<a href="' . $extra_info['guardian_howtheyvoted'] . '" title="At The Guardian">well-known issues</a> <small>(from the Guardian)</small>';
	}
	if ((isset($extra_info['public_whip_division_attendance']) && $extra_info['public_whip_division_attendance'] != 'n/a')
      || (isset($extra_info['Lpublic_whip_division_attendance']) && $extra_info['Lpublic_whip_division_attendance'] != 'n/a')) {
		$record[] = '<a href="http://www.publicwhip.org.uk/mp.php?id=uk.org.publicwhip/member/' . $member['member_id'] . '&amp;showall=yes#divisions" title="At Public Whip">their full record</a>';
	}

	if (count($record) > 0) {
		$displayed_stuff = 1;
		?>
		<p class="morelink">More on <?php echo implode(' &amp; ', $record); ?></p>
<?php
	}


	if (!$displayed_stuff) {
		print '<p>No data to display yet.</p>';
	}
}

function person_committees_and_topics_for_sidebar($member, $extra_info) {
    $out = '<div class="block">';
    $topics_block_empty = true;

    // Select committee membership
    if (array_key_exists('office', $extra_info)) {
        $mins = array();

        foreach ($extra_info['office'] as $row) {
            if ($row['to_date'] == '9999-12-31' && $row['source'] == 'chgpages/selctee' || $row['source'] == 'datadotparl/committee' ) {
                $m = prettify_office($row['position'], $row['dept']);
                if ($row['from_date']!='2004-05-28') {
                    $m .= ' <small>(since ' . format_date($row['from_date'], SHORTDATEFORMAT) . ')</small>';
                }
                $mins[] = $m;
            }
        }

        if ($mins) {
            $topics_block_empty = false;

            $out .=  "<h4>Select Committee membership</h4>";
            $out .=  '<ul class="no-bullet">';
            foreach ($mins as $min) {
                $out .=  '<li>' . $min . '</li>';
            }
            $out .=  "</ul>";
        }
    }

    $wrans_dept = false;
    $wrans_dept_1 = null;
    $wrans_dept_2 = null;

    if (isset($extra_info['wrans_departments'])) {
        $wrans_dept = true;
        $subjects = explode(',', $extra_info['wrans_departments']);
        $wrans_dept_1 = '<span class="radius label">' . implode( '</span> <span class="radius label">', $subjects ) . '</span>';
    }

    if (isset($extra_info['wrans_subjects'])) {
        $wrans_dept = true;
        $subjects = explode(',', $extra_info['wrans_subjects']);
        $wrans_dept_2 = '<span class="radius label">' . implode( '</span> <span class="radius label">', $subjects ) . '</span>';
    }

    $topics  = '';
    if ($wrans_dept) {
        $topics_block_empty = false;

        $topics .= '<p class="interests">';
        if ($wrans_dept_1) { $topics .=  $wrans_dept_1; }
        if ($wrans_dept_2) { $topics .=  $wrans_dept_2; }
        $topics .= '</p>';

        $WRANSURL = new URL('search');
        $WRANSURL->insert(array('pid'=>$member['person_id'], 's'=>'section:wrans', 'pop'=>1));
        $out .= '<div class="moreinfo"><span class="moreinfo-text">Based on written questions asked by ' . $member['full_name'] . ' and answered by departments</span><a href="' . $WRANSURL->generate() . '"><img src="/images/questionmark.png"></a></div>';
    }

    $out .= '<h4>Topics of interest</h4>';
    $out .= $topics;

    # Public Bill Committees
    if (count($extra_info['pbc'])) {
        $topics_block_empty = false;
        $out .=  '<h4>Public Bill Committees <small>(sittings attended)</small></h4>';

        if ($member['party'] == 'Scottish National Party') {
            $out .=  '<p><em>SNP MPs only attend sittings where the legislation pertains to Scotland.</em></p>';
        }

        $out .=  '<ul class="no-bullet">';
        foreach ($extra_info['pbc'] as $bill_id => $arr) {
            $out .=  '<li>';

            if ($arr['chairman']) {
                $out .=  'Chairman, ';
            }
            $out .=  '<a href="/pbc/' . $arr['session'] . '/' . urlencode($arr['title']) . '">'
            . $arr['title'] . ' Committee</a> <small>(' . $arr['attending']
            . ' out of ' . $arr['outof'] . ')</small>';
        }
        $out .=  '</ul>';
    }

    if ($topics_block_empty) {
        $out .=  "<p><em>This MP is not currently on any public bill committee
        and has had no written questions answered for which we know the department or subject.</em></p>";
    }
    $out .= "</div>";

    return $out;
}

function person_committees_and_topics($member, $extra_info) {
	$chairmens_panel = false;
echo'<h2>Topics of interest</h2>';
	$topics_block_empty = true;

	// Select committee membership
	if (array_key_exists('office', $extra_info)) {
		$mins = array();
		foreach ($extra_info['office'] as $row) {
			if ($row['to_date'] == '9999-12-31' && ( $row['source'] == 'chgpages/selctee' || $row['source'] == 'datadotparl/committee' ) ) {
				$m = prettify_office($row['position'], $row['dept']);
				if ($row['from_date']!='2004-05-28')
					$m .= ' <small>(since ' . format_date($row['from_date'], SHORTDATEFORMAT) . ')</small>';
				$mins[] = $m;
				if ($row['dept'] == "Chairmen's Panel Committee")
					$chairmens_panel = true;
			}
		}
		if ($mins) {
			print "<h3>Select Committee membership</h3>";
			print '<ul class="no-bullet">';
			foreach ($mins as $min) {
				print '<li>' . $min . '</li>';
			}
			print "</ul>";
			$topics_block_empty = false;
		}
	}
	$wrans_dept = false;
	$wrans_dept_1 = null;
	$wrans_dept_2 = null;
	if (isset($extra_info['wrans_departments'])) {
			$wrans_dept = true;
			$wrans_dept_1 = "<li><strong>Departments:</strong> ".$extra_info['wrans_departments']."</p>";
	}
	if (isset($extra_info['wrans_subjects'])) {
			$wrans_dept = true;
			$wrans_dept_2 = "<li><strong>Subjects (based on headings added by Hansard):</strong> ".$extra_info['wrans_subjects']."</p>";
	}

	if ($wrans_dept) {
		print "<p><strong>Asks most questions about</strong></p>";
		print '<ul class="no-bullet">';
		if ($wrans_dept_1) print $wrans_dept_1;
		if ($wrans_dept_2) print $wrans_dept_2;
		print "</ul>";
		$topics_block_empty = false;
		$WRANSURL = new URL('search');
		$WRANSURL->insert(array('pid'=>$member['person_id'], 's'=>'section:wrans', 'pop'=>1));
	?>							<p><small>(based on <a href="<?=$WRANSURL->generate()?>">written questions asked by <?=$member['full_name']?></a> and answered by departments)</small></p><?
	}

	# Public Bill Committees
	if (count($extra_info['pbc'])) {
		$topics_block_empty = false;
		print '<h3>Public Bill Committees <small>(sittings attended)</small></h3>';
		if ($member['party'] == 'Scottish National Party') {
			echo '<p><em>SNP MPs only attend sittings where the legislation pertains to Scotland.</em></p>';
		}
		echo '<ul class="no-bullet">';
		foreach ($extra_info['pbc'] as $bill_id => $arr) {
			print '<li>';
			if ($arr['chairman']) print 'Chairman, ';
			print '<a href="/pbc/' . $arr['session'] . '/' . urlencode($arr['title']) . '">'
				. $arr['title'] . ' Committee</a> <small>(' . $arr['attending']
				. ' out of ' . $arr['outof'] . ')</small>';
		}
		print '</ul>';
	}

	if ($topics_block_empty) {
		print "<p><em>This MP is not currently on any public bill committee
and has had no written questions answered for which we know the department or subject.</em></p>";
	}

	$member['chairmens_panel'] = $chairmens_panel;
}

# ---

function display_stats_line($category, $blurb, $type, $inwhat, $afterstuff, $extra_info, $minister = false, $Lminister = false) {
	$return = false;
	if (isset($extra_info[$category]))
		$return = display_stats_line_house(HOUSE_TYPE_COMMONS, $category, $blurb, $type, $inwhat, $extra_info, $minister, $afterstuff);
	if (isset($extra_info["L$category"]))
		$return = display_stats_line_house(HOUSE_TYPE_LORDS, "L$category", $blurb, $type, $inwhat, $extra_info, $Lminister, $afterstuff);
	return $return;
}

function display_stats_line_house($house, $category, $blurb, $type, $inwhat, $extra_info, $minister, $afterstuff) {
	if ($category == 'wrans_asked_inlastyear' || $category == 'debate_sectionsspoken_inlastyear' || $category =='comments_on_speeches' ||
		$category == 'Lwrans_asked_inlastyear' || $category == 'Ldebate_sectionsspoken_inlastyear' || $category =='Lcomments_on_speeches') {
		if ($extra_info[$category]==0) {
			$blurb = preg_replace('#<a.*?>#', '', $blurb);
			$inwhat = preg_replace('#<\/a>#', '', $inwhat);
		}
	}
	if ($house==HOUSE_TYPE_LORDS) $inwhat = str_replace('MP', 'Lord', $inwhat);
	print '<li>' . $blurb;
	print '<strong>' . $extra_info[$category];
	if ($type) print ' ' . make_plural($type, $extra_info[$category]);
	print '</strong>';
	print $inwhat;
	if ($minister===2) {
		print ' &#8212; Speakers/ deputy speakers do not ask written questions';
	} elseif ($minister)
		print ' &#8212; Ministers do not ask written questions';
	else {
		$type = ($house==HOUSE_TYPE_COMMONS?'MP':($house==HOUSE_TYPE_LORDS?'Lord':'MLA'));
		if (!get_http_var('rem') && isset($extra_info[$category . '_quintile'])) {
			print ' &#8212; ';
			$q = $extra_info[$category . '_quintile'];
			if ($q == 0) {
				print 'well above average';
			} elseif ($q == 1) {
				print 'above average';
			} elseif ($q == 2) {
				print 'average';
			} elseif ($q == 3) {
				print 'below average';
			} elseif ($q == 4) {
				print 'well below average';
			} else {
				print '[Impossible quintile!]';
			}
			print ' amongst ';
			print $type . 's';
		} elseif (!get_http_var('rem') && isset($extra_info[$category . '_rank'])) {
			print ' &#8212; ';
			#if (isset($extra_info[$category . '_rank_joint']))
			#	print 'joint ';
			print make_ranking($extra_info[$category . '_rank']) . ' out of ' . $extra_info[$category . '_rank_outof'];
			print ' ' . $type . 's';
		}
	}
	print ".$afterstuff";
	return true;
}

function display_writetothem_numbers($year, $extra_info) {
	if (isset($extra_info["writetothem_responsiveness_notes_$year"])) {
	?><li>Responsiveness to messages sent via <a href="http://www.writetothem.com/stats/<?=$year?>/mps">WriteToThem.com</a> in <?=$year?>: <?=$extra_info["writetothem_responsiveness_notes_$year"]?>.</li><?
		return true;
	} elseif (isset($extra_info["writetothem_responsiveness_mean_$year"])) {
		$mean = $extra_info["writetothem_responsiveness_mean_$year"];

		$a = $extra_info["writetothem_responsiveness_fuzzy_response_description_$year"];
		if ($a == 'very low') $a = 'a very low';
		if ($a == 'low') $a = 'a low';
		if ($a == 'medium') $a = 'a medium';
		if ($a == 'high') $a = 'a high';
		if ($a == 'very high') $a = 'a very high';
		$extra_info["writetothem_responsiveness_fuzzy_response_description_$year"] = $a;

		return display_stats_line("writetothem_responsiveness_fuzzy_response_description_$year", 'Replied within 2 or 3 weeks to <a href="http://www.writetothem.com/stats/'.$year.'/mps" title="From WriteToThem.com">', "", "</a> <!-- Mean: " . $mean . " --> number of messages sent via WriteToThem.com during ".$year.", according to constituents", "", $extra_info);
	}

}

function person_speaker_special($member, $extra_info) {
    global $PAGE;

    if ( !(isset($extra_info["is_speaker_candidate"]) && $extra_info["is_speaker_candidate"] == 1 && isset($extra_info["speaker_candidate_contacted_on"]))
      && !(isset($extra_info['speaker_candidate_response']) && $extra_info['speaker_candidate_response']) ) {
        return;
    }

    $just_response = false;
    if ($extra_info['is_speaker_candidate'] == 0) {
        $just_response = true;
    }

    // days since originally contacted
    $contact_date_string = $extra_info["speaker_candidate_contacted_on"];
    $contact_date_midnight = strtotime($contact_date_string);
    $days_since_contact = floor((time() - $contact_date_midnight) / 86400);
    if ($days_since_contact == 1) {
        $days_since_string = $days_since_contact . ' day ago';
    } elseif($days_since_contact > 1) {
        $days_since_string = $days_since_contact . ' days ago';
    } else {
        $days_since_string = 'today';
    }

    $reply_time = "*unknown*";
    if (isset($extra_info["speaker_candidate_replied_on"])) {
        $reply_date_string = $extra_info["speaker_candidate_replied_on"];
        $reply_date_midnight = strtotime($reply_date_string);
        $days_for_reply = floor(($reply_date_midnight - $contact_date_midnight) / 86400);
        if ($days_for_reply == 0) {
            $reply_time = "in less than 24 hours";
        } elseif($days_for_reply == 1) {
            $reply_time = "in 1 day";
        } else {
            $reply_time = "in $days_for_reply days";
        }
    }

    if ($just_response) {
        $spk_cand_title = $member['full_name'] . ' endorses our Speaker principles';
    } else {
        if (isset($extra_info["speaker_candidate_elected"]) && $extra_info["speaker_candidate_elected"] == 1) {
            $spk_cand_title = 'LATEST: ' . $member['full_name'] . ' elected Speaker. Here\'s what he endorsed:';
        } else {
            $spk_cand_title = 'IMPORTANT: ' . $member['full_name'] . ' was a Candidate for Speaker.';
        }
    }
    $PAGE->block_start(array('id'=>'campaign_block', 'title' => $spk_cand_title));

    if (!isset($extra_info["speaker_candidate_response"])){
        print "
                You can help make sure that all the candidates understand that they
                must be a strong, Internet-savvy proponents of a better, more
                accountable era of democracy.";
    }
    print "</p>

            <p>mySociety asked likely candidates for the post of Speaker to endorse the
            following principles." ;

    print "<p><strong>The three principles are:</strong></p>

            <ol>

               <li> Voters have the right to know in <strong>detail about the money</strong> that is spent to
            support MPs and run Parliament, and in similar detail how the decisions to
            spend that money are settled upon. </li>

               <li> Bills being considered must be published online in a much better way than
            they are now, as the <strong>Free Our Bills</strong> campaign has been suggesting for some time. </li>

               <li> The Internet is not a threat to a renewal in our democracy, it is one of
            its best hopes. Parliament should appoint a senior officer with direct working
            experience of the <strong>power of the Internet</strong> who reports directly to the Speaker,
            and who will help Parliament adapt to a new era of transparency and
            effectiveness. </li>

            </ol>";

    if (isset($extra_info["speaker_candidate_response"]) && $extra_info["speaker_candidate_response"]) {
        print "</p><p><strong><big>Update: " . $member['full_name'] . " MP replied $reply_time. " . $extra_info["speaker_candidate_response_summary"] . " Here's the reply in full: </big></strong></p>";
        print "<blockquote><div id='speaker_candidate_response'>";
        print $extra_info["speaker_candidate_response"];
        print "</div></blockquote>";
    } else {
        print "<p> We contacted " . $member['full_name'] . " MP to ask for an endorsement " . $days_since_string . ". ";
    	print "They have not yet replied.</p>";
    }
    $PAGE->block_end();
}

