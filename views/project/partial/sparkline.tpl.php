<?php

include_once SYSTEM_PATH."/lib/simpleEncode.php";

$data = $SOUP->get('data', array());
$project = $SOUP->get('project');
$filter = $SOUP->get('filter');

if(STYLE_SHEET == 'dark.css') {
	$lineColor = 'cccccc';
	$bgColor = '222222';
} else {
	$lineColor = '666666';
	$bgColor = 'ffffff';
}

$height=50;
$width=570;

?>

<script type="text/javascript">

$(document).ready(function(){
	$('#selActivityFilter').val('<?= $filter ?>');
	$('#selActivityFilter').change(function(){
		var url = '<?= Url::activity($project->getID()) ?>';
		var filter = $('#selActivityFilter').val();
		if(filter != '')
			url = url+'/'+filter; 
		window.location = url;
	});
	$('#selActivityFilter').focus();
});

</script>

<div class="panel large">
	<div class="panel-header">
		<h4>Activity This Week</h4>
		<div class="activity-filter">
			<select id="selActivityFilter">
				<option value="">show all activity</option>
				<option value="basics">Basics only</option>
				<option value="tasks">Tasks only</option>
				<option value="discussions">Discussions only</option>
				<option value="people">People only</option>
			</select>
		</div>
	</div>
	<div class="panel-body">
		<img class="sparkline" src="http://chart.apis.google.com/chart?cht=lc&chs=<?= $width ?>x<?= $height ?>&chd=<?= simpleEncode($data,max($data)) ?>&chco=<?= $lineColor ?>&chls=1,1,0&chxt=r,x,y&chxs=0,<?= $lineColor ?>,11,0,_|1,<?= $lineColor ?>,1,0,_|2,<?= $lineColor ?>,1,0,_&chxl=0:||1:||2:||&chf=bg,s,<?= $bgColor ?>" /></li>
	</div>
</div>