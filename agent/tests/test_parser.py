import unittest

from athar_agent.parser import parse_packet_summary


class PacketSummaryParserTest(unittest.TestCase):
    def test_parser_normalizes_valid_tshark_packet_summary(self):
        raw = "frame.number,frame.time_epoch,ip.src,ip.dst,_ws.col.Protocol,frame.len\n1,1720000000.0,10.0.0.1,8.8.8.8,DNS,74\n"
        self.assertEqual(parse_packet_summary(raw), [{"frame_number": 1, "timestamp": "1720000000.0", "source_ip": "10.0.0.1", "destination_ip": "8.8.8.8", "protocol": "DNS", "frame_length": 74}])

    def test_parser_rejects_unexpected_headers(self):
        with self.assertRaises(ValueError):
            parse_packet_summary("frame.number,protocol\n1,DNS\n")
