# ATHAR Forensic Agent

This package is the CAINE-side execution component for ATHAR. It is deliberately separate from Laravel because it needs controlled access to forensic tools inside CAINE 14.0.

The starter implements policy validation and safe argument-array construction for the first `tshark.packet_summary` operation. It does not accept raw shell text, arbitrary paths, or unknown options. The network client will be enabled only after the Laravel Agent API and local VirtualBox network route are configured.
