import unittest

from athar_agent.api import AtharApi


class AtharApiConfigurationTest(unittest.TestCase):
    def test_https_is_required_for_non_localhost_agent_endpoints(self):
        with self.assertRaises(ValueError):
            AtharApi("http://192.168.56.1:8000", "token")

    def test_explicit_localhost_override_is_permitted_for_local_development_only(self):
        api = AtharApi("http://127.0.0.1:8000", "token", allow_insecure_localhost=True)
        self.assertEqual(api.base_url, "http://127.0.0.1:8000")
