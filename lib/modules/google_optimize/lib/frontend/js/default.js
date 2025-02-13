window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());

gtag('config', 'GA_TRACKING_ID', { 'optimize_id': '<?php echo $script->get_parent()->get_setting('tracking_id')->get_data(); ?>'});