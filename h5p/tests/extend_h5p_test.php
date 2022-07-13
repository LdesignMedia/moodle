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

/**
 * Testing the H5P PLAYER extend by plugin.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @package    core_h5p
 * @category   test
 * @copyright  2022-06-13 Mfreak.nl | LdesignMedia.nl - Luuk Verhoeven
 * @author     Hamza Tamyachte
 **/

namespace core_h5p;

/**
 * Testing the H5P PLAYER.
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @package   core_h5p
 * @copyright 2022-06-13 Mfreak.nl | LdesignMedia.nl - Luuk Verhoeven
 * @author    Hamza Tamyachte
 * @coversDefaultClass \core_h5p\player
 **/
class extend_h5p_test extends \advanced_testcase {

    /** @var \core_h5p\player */
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
     * Test the behaviour of add_assets_to_page
     *
     * @dataProvider add_assets_to_page_provider
     * @param string $embedtype
     *
     * @return void
     */
    public function test_add_assets_to_page(string $embedtype): void {
        global $OUTPUT, $DB;

        $this->setRunTestInSeparateProcess(true);

        // Reset database after test.
        $this->resetAfterTest(true);

        $this->player->add_assets_to_page();

        $h5p = $DB->get_record('h5p', []);
        $template = (object) [
            'h5pid' => $h5p->id,
        ];

        $h5pjson = json_decode($h5p->jsoncontent, false, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals($h5pjson->title, $this->player->get_title());

        if ($embedtype === 'div') {
            $h5phtml = $OUTPUT->render_from_template('core_h5p/h5pdiv', $template);
        } else {
            $h5phtml = $OUTPUT->render_from_template('core_h5p/h5piframe', $template);
        }

        $this->assertEquals($this->player->output(), $h5phtml);
    }

    /**
     * Data provider for test_add_assets_to_page().
     *
     * @return array
     */
    public function add_assets_to_page_provider(): array {
        return [
            'player: embedtype is iframe' => [
                'embedtype' => 'iframe',
            ],
        ];
    }

    /**
     * Test the behaviour of load_files_plugin_callbacks callback
     *
     * @dataProvider load_files_plugin_callbacks_provider
     *
     * @param string $type
     * @param string $embedtype
     *
     * @return void
     */
    public function test_load_files_plugin_callbacks(string $type, string $embedtype): void {

        $this->set_up();

        $this->setRunTestInSeparateProcess(true);
        // Reset database after test.
        $this->resetAfterTest(true);

        $reflector = new \ReflectionClass(player::class);
        $loadfilesplugincallbacks = $reflector->getMethod('load_files_plugin_callbacks');
        $loadfilesplugincallbacks->setAccessible(true);
        $paths = $loadfilesplugincallbacks->invokeArgs($this->player, [$type]);

        $pluginsfunction = get_plugins_with_function('extend_h5p_' . $type);
        $files = [];
        foreach ($pluginsfunction as $plugins) {
            foreach ($plugins as $pluginfunction) {
                $files = $pluginfunction($embedtype);
            }
        }
        $expectedpaths = array_map(static function($path) {
            return (object) ['path' => (string) $path];
        }, $files);

        $this->assertCount(count($expectedpaths), $paths);

        foreach ($expectedpaths as $key => $expectedpath) {
            $this->assertEquals($expectedpath->path, $paths[$key]->path);
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
                'embedtype' => 'iframe',
            ],
            'player:Plugin function type is styles and embedtype is iframe.' => [
                'type' => 'styles',
                'embedtype' => 'iframe',
            ],
        ];
    }

}
