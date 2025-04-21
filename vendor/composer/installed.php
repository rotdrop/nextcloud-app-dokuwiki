<?php return array(
    'root' => array(
        'name' => 'rotdrop/dokuwiki-nextcloud',
        'pretty_version' => 'dev-master',
        'version' => 'dev-master',
        'reference' => 'c865dca3d14feb147c90a82f9dd098fae146dee8',
        'type' => 'project',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => false,
    ),
    'versions' => array(
        'phpxmlrpc/phpxmlrpc' => array(
            'pretty_version' => '4.11.1',
            'version' => '4.11.1.0',
            'reference' => '06b9d7275d637f6859527091a54a5edfe8a16749',
            'type' => 'library',
            'install_path' => __DIR__ . '/../phpxmlrpc/phpxmlrpc',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'rotdrop/dokuwiki-nextcloud' => array(
            'pretty_version' => 'dev-master',
            'version' => 'dev-master',
            'reference' => 'c865dca3d14feb147c90a82f9dd098fae146dee8',
            'type' => 'project',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'symfony/console' => array(
            'dev_requirement' => false,
            'provided' => array(
                0 => '*',
            ),
        ),
        'symfony/event-dispatcher' => array(
            'dev_requirement' => false,
            'provided' => array(
                0 => '*',
            ),
        ),
        'symfony/process' => array(
            'dev_requirement' => false,
            'provided' => array(
                0 => '*',
            ),
        ),
    ),
);
