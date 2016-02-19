<?php
	require_once("class-aria-create-competition.php");
	require_once("class-aria-music.php");
	$teacher_fields = ARIA_Create_Competition::aria_teacher_field_id_array();
	$music_fields = ARIA_Music::aria_music_field_id_array();
	$all_fields = array_merge( $teacher_fields, $music_fields );
	echo json_encode($all_fields);
?>
