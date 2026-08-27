<?php

return [
    'evidence_disk' => env('ATHAR_EVIDENCE_DISK', 'private'),
    'max_evidence_upload_kilobytes' => (int) env('ATHAR_MAX_EVIDENCE_UPLOAD_KILOBYTES', 20 * 1024),
    'agent_poll_path' => env('ATHAR_AGENT_POLL_PATH', '/api/v1/agent/jobs/claim'),
    'agent_result_limit_bytes' => (int) env('ATHAR_AGENT_RESULT_LIMIT_BYTES', 10 * 1024 * 1024),

    /*
     * Every executable operation must be declared here and mirrored by the
     * CAINE Agent policy. User-supplied command strings are never accepted.
     */
    'tools' => [
        'tshark' => [
            'display_name' => 'TShark',
            'verification_state' => 'installed_path_observed',
            'observed_path' => '/usr/bin/tshark',
            'operations' => [
                'packet_summary' => [
                    'evidence_types' => ['pcap', 'pcapng'],
                    'timeout_seconds' => 300,
                    'max_output_bytes' => 10 * 1024 * 1024,
                    'allowed_parameters' => [],
                    'adapter_key' => 'tshark.packet_summary',
                ],
            ],
        ],
    ],
];
