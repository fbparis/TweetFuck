TweetFuck
=========

Use Twitter API without OAuth and without restrictions.

Basic example:

	<?php
	$T = new TweetFuck;
	if (!$T->signin('username_or_email','password')) die("Unable to sign in\n");
	print_r($T->post('statuses/update',array('status'=>'Hello world')));
	print_r($T->get('account/rate_limit_status'));
	?>