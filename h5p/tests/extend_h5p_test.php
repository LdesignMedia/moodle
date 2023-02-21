<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace core_h5p;

/**
 * Testing the H5P PLAYER extend by plugin.
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @package   core_h5p
 * @copyright 2023 Mfreak.nl | LdesignMedia.nl - Luuk Verhoeven
 * @author    Hamza Tamyachte
 * @coversDefaultClass \core_h5p\player
 **/
class extend_h5p_test extends \advanced_testcase {

    /** @var player */
    protected $player;

    /**
     * Set up function for tests.
     */
    protected function setUp(): void {

        $config = (object) [
            'frame' => 1,
            'export' => 1,
            'embed' => 0,
            'copyright' => 0,
        ];

        // Create the H5P data.
        $filename = 'find-the-words.h5p';
        $path = __DIR__ . '/fixtures/' . $filename;
        $fakefile = helper::create_fake_stored_file_from_path($path);

        // Get URL for this H5P content file.
        $syscontext = \context_system::instance();
        $url = \moodle_url::make_pluginfile_url(
            $syscontext->id,
            'core_h5p',
            'unittest',
            $fakefile->get_itemid(),
            '/',
            $filename
        );

        $this->player = new player($url->out(), $config);

    }

    /**
     * Test the behaviour of load_files_plugin_callbacks callback.
     *
     * @dataProvider load_files_plugin_callbacks_provider
     * @covers       \core_h5p\player::load_files_plugin_callbacks
     *
     * @param string $type
     *
     * @return void
     */
    public function test_load_files_plugin_callbacks(string $type): void {

        $this->setUp();

        $this->setRunTestInSeparateProcess(true);
        // Reset database after test.
        $this->resetAfterTest(true);

        $reflector = new \ReflectionClass(player::class);
        $loadfilesplugincallbacks = $reflector->getMethod('load_files_plugin_callbacks');
        $loadfilesplugincallbacks->setAccessible(true);
        $files = $loadfilesplugincallbacks->invokeArgs($this->player, [$type]);

        foreach ($files as $file) {

            $this->assertTrue(array_key_exists('path', (array) $file), 'file has path or not');
        }

    }

    /**
     * Data provider for load_files_plugin_callbacks().
     *
     * @return array
     */
    public function load_files_plugin_callbacks_provider(): array {
        return [
            'player: Plugin function type is scripts and embedtype is iframe.' => [
                'type' => 'scripts',
            ],
            'player:Plugin function type is styles and embedtype is iframe.' => [
                'type' => 'styles',
            ],
        ];
    }

}
