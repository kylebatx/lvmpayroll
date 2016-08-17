<?php
$parse_uri = explode( 'wp-content', $_SERVER['SCRIPT_FILENAME'] );
require_once( $parse_uri[0] . 'wp-load.php' );
global $wpdb;
	$week = $_POST['week'];
	$weekID = substr($week, 0, strpos($week, '_')); 
	$yearID = substr($week, strpos($week, "_") + 1);     
	
function getStartAndEndDate($week, $year) {
  $dto = new DateTime();
  $dto->setISODate($year, $week);
  $ret['0'] = $dto->format('Y-m-d');
  $dto->modify('+6 days');
  $ret['1'] = $dto->format('Y-m-d');
  return $ret;
}

$pay_week = getStartAndEndDate($weekID, $yearID);

$date = date("Y-m-d", strtotime($_POST['date']));
$start = strtotime($_POST['start']);
$end = strtotime($_POST['end']);
$table_name = $wpdb->prefix . 'wpc_pm_timesheets';

$start_time = date("H:i:s", $start);
$end_time = date("H:i:s", $end);
	
function convertTime($dec)
{
	$seconds = (int)($dec * 3600);
	$hours = floor($dec);
	$seconds -= $hours * 3600;
	$minutes = floor($seconds / 60);
	return $hours." hr ".$minutes." min";
}
	
/*$all_timesheets = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wpc_pm_timesheets ORDER BY date ASC", ARRAY_A ); */ //Gets all weeks for existing timesheets
	
$meta_key1 = $pay_week[0];
$meta_key2 = $pay_week[1];
$meta_key3 = 'last_name';
$demo_reps = $wpdb->get_col( $wpdb->prepare(
	"
	SELECT DISTINCT key1.user_id 
	FROM 		{$wpdb->prefix}wpc_pm_timesheets key1 
	INNER JOIN 	$wpdb->usermeta key2 
				ON key2.user_id = key1.user_id 
				AND key2.meta_key = %s
	WHERE 		key1.date >= %s
				AND key1.date <= %s
	ORDER BY 	key2.meta_value ASC, key1.date ASC",  
	$meta_key3, $meta_key1, $meta_key2 )); //Gets all projects that are assigned to current user

if ($demo_reps) {
foreach ($demo_reps as $demo_rep) {
	$first_name = get_user_meta( $demo_rep, 'first_name', true );
	$last_name = get_user_meta( $demo_rep, 'last_name', true );
	if( !$first_name && !$last_name ) {
		$user = get_userdata( $demo_rep );
		$alt = isset( $user->user_login ) ? $user->user_login : '';
	} else {
		$alt = $last_name . ', ' . $first_name;
	}
	echo '<div id="user' . $demo_rep . '" class="mk-toggle fancy-style">';
	$week_time = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wpc_pm_timesheets WHERE user_id={$demo_rep} AND date BETWEEN '{$pay_week[0]}' AND '{$pay_week[1]}' ORDER BY date ASC", ARRAY_A );
	$diff = 0;
	foreach ($week_time as $day_total) {
		$start = strtotime($day_total[start]);
		$end = strtotime($day_total[end]);
		if ($diff == 0) $diff = abs($end - $start)/3600;
		else $diff += abs($end - $start)/3600;
	}
	
		$total_time = convertTime($diff);
	echo '<span class="mk-toggle-title ' . $active_toggle . '"><i class="mk-moon-plus"></i><span>' . $alt . ' / <span style="color:#ff0000">' . $total_time . '</span></span></span>';
	echo '</div>'; ?>
	<div id="user<?php echo $demo_rep; ?>_content" class="mk-toggle-pane" style="display:none;">
	<table class="demo-list">
	<thead>
		<tr>
			<th>Date</th>
			<th>Demo Name</th>
			<th>Vendor</th>
			<th>Login Sheet</th>
			<th>Time Worked</th>
			<th>Total</th>
		</tr>
	</thead>
	<tbody>
	<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('#user<?php echo $demo_rep; ?>').click(function(){
			jQuery(this).find('.mk-toggle-title').toggleClass('active-toggle');
			jQuery('#user<?php echo $demo_rep; ?>_content').slideToggle('fast');
		});
	});
	</script>
	<?php $all_timesheets = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wpc_pm_timesheets WHERE user_id={$demo_rep} AND date BETWEEN '{$pay_week[0]}' AND '{$pay_week[1]}' ORDER BY date ASC", ARRAY_A );
	foreach ($all_timesheets as $timesheet) {
	?>
		<tr>
			<td><?php	echo date("M j", strtotime($timesheet[date])); ?></td>
			<td><?php 	$demo = $wpdb->get_row( "SELECT * FROM {$wpdb->prefix}wpc_pm_projects WHERE id={$timesheet[id]}", ARRAY_A );
						echo stripslashes($demo[title]); ?></td>
			<td><?php if( isset( $demo['client_id'] ) && is_numeric( $demo['client_id'] ) && 0 < $demo['client_id'] ) { $business_name = get_user_meta( $demo['client_id'], 'wpc_cl_business_name', true ); echo( $business_name ? $business_name : get_userdata( $demo['client_id'] )->data->user_login ); } else echo '<span style="color:#ff0000;">Not Set</span>' ; ?></td>
			<td><?php 
						$login = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wpc_pm_files WHERE object_id={$timesheet[id]} AND type='message'", ARRAY_A );
						foreach ($login as $loginimage) {
						echo '<a href="/wp-content/uploads/demo_reports/' . $timesheet[id] . '/' . $loginimage[filename] . '" class="mk-lightbox" data-fancybox-group="images-shortcode-' . $demo_rep . '">View</a>' ;}
		  ?></td>
			<td>
				<?php 	echo date("g:ia", strtotime($timesheet[start])) . ' - ' . date("g:ia", strtotime($timesheet[end]));
				?>
			</td>
			<td><?php 
						$start = strtotime($timesheet[start]);
						$end = strtotime($timesheet[end]);
						$diff = abs($end - $start)/3600;
						$total = convertTime($diff);
						echo $total; ?></td>
		</tr>
	<?php } ?>
	</tbody>
	</table>
	</div>
	<?php
}
} else {
	echo 'no results';
}
	
	echo '<hr />';
?>
