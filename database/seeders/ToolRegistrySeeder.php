<?php

namespace Database\Seeders;

use App\Models\ForensicTool;
use App\Models\ToolCategory;
use App\Models\ToolOperation;
use Illuminate\Database\Seeder;

class ToolRegistrySeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            'Analysis' => [['Autopsy', 'autopsy'], ['The Sleuth Kit', 'tsk'], ['Plaso', 'plaso-analysis']],
            'Database' => [['DB Browser for SQLite', 'sqlite-browser'], ['DBeaver', 'dbeaver'], ['SQLite tooling', 'sqlite']],
            'Disks' => [['TestDisk', 'testdisk'], ['PhotoRec', 'photorec'], ['GNU ddrescue', 'ddrescue']],
            'Hash' => [['hashdeep', 'hashdeep'], ['sha256sum', 'sha256sum'], ['QuickHash', 'quickhash']],
            'Malware' => [['YARA', 'yara'], ['ClamAV', 'clamav'], ['CAPE Sandbox', 'cape']],
            'Memory Forensics' => [['Volatility 3', 'volatility3'], ['MemProcFS', 'memprocfs'], ['Volatility legacy', 'volatility-legacy']],
            'Mobile Forensics' => [['ADB', 'adb'], ['libimobiledevice', 'libimobiledevice'], ['gMTP', 'gmtp']],
            'Network Forensics' => [['Wireshark', 'wireshark'], ['TShark', 'tshark'], ['Zeek', 'zeek']],
            'OSINT' => [['theHarvester', 'theharvester'], ['SpiderFoot', 'spiderfoot'], ['Maltego', 'maltego']],
            'Timeline' => [['Plaso', 'plaso-timeline'], ['log2timeline', 'log2timeline'], ['Timesketch', 'timesketch']],
        ];

        foreach ($catalog as $categoryName => $tools) {
            $category = ToolCategory::query()->updateOrCreate(['slug' => str($categoryName)->slug()->toString()], ['name' => $categoryName]);
            foreach ($tools as [$name, $slug]) {
                $isTshark = $slug === 'tshark';
                $tool = ForensicTool::query()->updateOrCreate(['slug' => $slug], [
                    'category_id' => $category->getKey(),
                    'name' => $name,
                    'adapter_key' => $slug,
                    'verification_state' => $isTshark ? 'installed_path_observed' : 'cataloged',
                    'enabled' => $isTshark,
                ]);
                if ($isTshark) {
                    ToolOperation::query()->updateOrCreate(['tool_id' => $tool->getKey(), 'operation_key' => 'packet_summary'], [
                        'evidence_types_json' => ['pcap', 'pcapng'],
                        'input_schema_json' => [],
                        'output_schema_json' => ['frame_number', 'timestamp', 'source_ip', 'destination_ip', 'protocol', 'length'],
                        'timeout_seconds' => 300,
                        'max_output_bytes' => 10485760,
                        'enabled' => true,
                    ]);
                }
            }
        }
    }
}
