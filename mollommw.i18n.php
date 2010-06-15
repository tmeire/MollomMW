<?php
/**
 * Internationalisation file for MollomMW extension.
 *
 * @addtogroup Extensions
 */

$messages = array();

$messages['en'] = array(
	'mollommw-mollom-error'           => 'Something went wrong while contacting Mollom. If this problem persists, enable debugging and check the log file.',
	'mollommw-key-validation'         => 'Mollom Key Validation',
	'mollommw-key-validation-success' => 'Your Mollom keys are valid.',
	'mollommw-key-validation-failure' => 'Your Mollom keys are invalid! Check [http://www.mollom.com/user mollom.com] for your correct keys.',
	'mollommw-statistics'             => 'Statistics',

	'mollommw-word-verification'      => 'Word verification',
	'mollommw-captcha-toggle'         => 'Play audio captcha',
	'mollommw-possibly-spam'          => 'Your edit looks like spam. To confirm your edit is not spam, type the characters you see in the picture below. If you can\'t read them, submit the form and a new image will be generated. Not case sensitive.',
	'mollommw-spam'                   => 'You\'re message has been rejected as it\'s marked as spam.',

	'mollommw-blacklists'             => 'Blacklist Management',
	'mollommw-blacklist-addedon'      => '$1<br>Added on $2',
	'mollommw-blacklist-url-title'    => 'Url Blacklist',
	'mollommw-blacklist-url-add'      => 'Add url to blacklist',
	'mollommw-blacklist-url-remove'   => 'Remove url',
	'mollommw-blacklist-text-title'   => 'Text Blacklist',
	'mollommw-blacklist-text-add'     => 'Add text to blacklist',
	'mollommw-blacklist-text-remove'  => 'Remove text',

	'mollommw-blacklist-text'               => 'text',
	'mollommw-blacklist-context'            => 'context',
	'mollommw-blacklist-context-everything' => 'everything',
	'mollommw-blacklist-context-links'      => 'links',
	'mollommw-blacklist-context-author'     => 'author',
	'mollommw-blacklist-reason'             => 'reason',
	'mollommw-blacklist-reason-spam'        => 'spam',
	'mollommw-blacklist-reason-profanity'   => 'profanity',
	'mollommw-blacklist-reason-low-quality' => 'low kwaliteit',
	'mollommw-blacklist-reason-unwanted'    => 'unwanted',

	'right-mollommw-admin'      => 'MollomMW Administration',
	'right-mollommw-no-check'   => 'MollomMW doesn\'t perform a check on edits from this user.',
	'right-mollommw-no-captcha' => 'MollomMW doesn\'t show captcha\'s to this user when content spaminess is unsure.',

	'specialpages-group-mollommw'     => 'MollomMW Administration',
	// no need to translate the lines below
	'specialpages-specialpagegroup-mollommw-statistics' => 'mollommw',
	'specialpages-specialpagegroup-mollommw-blacklists' => 'mollommw',
);

$messages['nl'] = array(
	'mollommw-mollom-error'           => 'Er ging iets fout tijdens de communicatie met Mollom. Schakel de debug modus in en controlleer de log file als dit probleem zich blijft voordoen.',
	'mollommw-key-validation'         => 'Mollom Sleutel Verificatie',
	'mollommw-key-validation-success' => 'Je Mollom sleutels zijn correct geverifieerd.',
	'mollommw-key-validation-failure' => 'Je Mollom sleutels kunnen niet geverifieerd worden! Controlleer je sleutels op [http://www.mollom.com/user mollom.com].',
	'mollommw-statistics'             => 'Statistieken',

	'mollommw-word-verification'      => 'Woord verificatie',
	'mollommw-captcha-toggle'         => 'Speel een geluidscaptcha',
	'mollommw-possibly-spam'          => 'Je wijziging bevat verschillende kenmerken van spam. Typ de karakters uit onderstaande afbeelding over om te bevestigen dat de wijzigingen geen spam zijn. Sla de wijzigingen opnieuw op indien de afbeelding onleesbaar is, er zal dan een nieuwe afbeelding getoond worden. De karakters zijn niet hoofdlettergevoelig.',
	'mollommw-spam'                   => 'Je bericht werd geweigerd omdat het aangemerkt werd als spam.',

	'mollommw-blacklists'             => 'Beheer van zwarte lijsten',
	'mollommw-blacklist-addedon'      => '$1<br>Toegevoegd op $2',
	'mollommw-blacklist-url-title'    => 'Zwarte lijst voor url\'s',
	'mollommw-blacklist-url-add'      => 'Url toevoegen aan de zwarte lijst',
	'mollommw-blacklist-url-remove'   => 'Url verwijderen',
	'mollommw-blacklist-text-title'   => 'Zwarte lijst voor tekst',
	'mollommw-blacklist-text-add'     => 'Tekst toevoegen aan de zwarte lijst',
	'mollommw-blacklist-text-remove'  => 'Tekst verwijderen',

	'mollommw-blacklist-text'               => 'tekst',
	'mollommw-blacklist-context'            => 'context',
	'mollommw-blacklist-context-everything' => 'alles',
	'mollommw-blacklist-context-links'      => 'links',
	'mollommw-blacklist-context-author'     => 'auteur',
	'mollommw-blacklist-reason'             => 'reden',
	'mollommw-blacklist-reason-spam'        => 'spam',
	'mollommw-blacklist-reason-profanity'   => 'obsceen taalgebruik',
	'mollommw-blacklist-reason-low-quality' => 'lage kwaliteit',
	'mollommw-blacklist-reason-unwanted'    => 'ongewenst',

	'right-mollommw-admin'      => 'MollomMW Administratie',
	'right-mollommw-no-check'   => 'MollomMW voert geen controle voor spam uit op aanpassingen van deze gebruiker.',
	'right-mollommw-no-captcha' => 'MollomMW toont deze gebruiker geen captcha wanneer de edit mogelijk spam is.',

	'specialpages-group-mollommw'     => 'MollomMW Administratie',
);
