import unittest
from pathlib import Path
from tempfile import TemporaryDirectory

from athar_agent.policy import PolicyError, build_command


class ToolPolicyTests(unittest.TestCase):
    def setUp(self):
        self.workspace = TemporaryDirectory()
        self.evidence_root = Path(self.workspace.name)
        (self.evidence_root / "safe.pcap").write_bytes(b"placeholder")

    def tearDown(self):
        self.workspace.cleanup()

    def test_tshark_packet_summary_uses_fixed_argument_array(self):
        operation, command = build_command(
            {
                "tool_id": "tshark",
                "operation_key": "packet_summary",
                "evidence_reference": "safe.pcap",
            },
            self.evidence_root,
        )

        self.assertEqual(operation.binary, "/usr/bin/tshark")
        self.assertEqual(command[:4], ["/usr/bin/tshark", "-n", "-r", str(self.evidence_root / "safe.pcap")])
        self.assertNotIn("shell", " ".join(command).lower())

    def test_unknown_operation_is_rejected(self):
        with self.assertRaises(PolicyError):
            build_command(
                {
                    "tool_id": "tshark",
                    "operation_key": "arbitrary_command",
                    "evidence_reference": "safe.pcap",
                },
                self.evidence_root,
            )

    def test_evidence_path_escape_is_rejected(self):
        with self.assertRaises(PolicyError):
            build_command(
                {
                    "tool_id": "tshark",
                    "operation_key": "packet_summary",
                    "evidence_reference": "../outside.pcap",
                },
                self.evidence_root,
            )

    def test_non_pcap_input_is_rejected(self):
        with self.assertRaises(PolicyError):
            build_command(
                {
                    "tool_id": "tshark",
                    "operation_key": "packet_summary",
                    "evidence_reference": "malware.exe",
                },
                self.evidence_root,
            )


if __name__ == "__main__":
    unittest.main()
