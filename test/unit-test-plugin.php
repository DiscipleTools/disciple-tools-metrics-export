<?php

class PluginTest extends TestCase
{
    public function test_plugin_installed() {
        activate_plugin( 'disciple-tools-plugin-starter-template/disciple-tools-plugin-starter-template.php' );

        $this->assertContains(
            'disciple-tools-plugin-starter-template/disciple-tools-plugin-starter-template.php',
            get_option( 'active_plugins' )
        );
    }
}
