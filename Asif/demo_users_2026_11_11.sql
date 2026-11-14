-- Demo users for My Work testing (2026_11_11). Delete later: DELETE FROM users WHERE email LIKE 'demo.%@test.local';
INSERT INTO users (full_name, user_name, phone, email, designation_id, role_id, password) VALUES
('Demo Creator',      'demo_creator',      '01000000001', 'demo.creator@test.local',      13, 2,  '$2y$10$o0XOO8M8nALsZf/ayPEMbuBvNFmMNiOWrevfXWKYRHGuJxL2VMdA2'),
('Demo Assignee',     'demo_assignee',     '01000000002', 'demo.assignee@test.local',     13, 2,  '$2y$10$o0XOO8M8nALsZf/ayPEMbuBvNFmMNiOWrevfXWKYRHGuJxL2VMdA2'),
('Demo Reviewer',     'demo_reviewer',     '01000000003', 'demo.reviewer@test.local',     19, 22, '$2y$10$o0XOO8M8nALsZf/ayPEMbuBvNFmMNiOWrevfXWKYRHGuJxL2VMdA2'),
('Demo Collaborator', 'demo_collaborator', '01000000004', 'demo.collaborator@test.local', 13, 2,  '$2y$10$o0XOO8M8nALsZf/ayPEMbuBvNFmMNiOWrevfXWKYRHGuJxL2VMdA2'),
('Demo CC',           'demo_cc',           '01000000005', 'demo.cc@test.local',           13, 2,  '$2y$10$o0XOO8M8nALsZf/ayPEMbuBvNFmMNiOWrevfXWKYRHGuJxL2VMdA2'),
('Demo Admin',        'demo_admin',        '01000000006', 'demo.admin@test.local',        19, 1,  '$2y$10$o0XOO8M8nALsZf/ayPEMbuBvNFmMNiOWrevfXWKYRHGuJxL2VMdA2');
