<?php
/**
 * Public Vote Verification Page
 * Anyone can access this — no login required
 * Verifies election integrity without revealing voter identity
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Guard: require DB
requireDb($pdo, $db_error ?? null);

// Get ledger info
$ledgerInfo = getVotingLedgerInfo($pdo);
$merkleInfo = computeMerkleRoot($pdo);

// Handle receipt verification
$receiptResult = null;
$receiptToken = $_GET['receipt'] ?? ($_POST['receipt'] ?? '');
if (!empty($receiptToken)) {
    $receiptToken = trim($receiptToken);
    $stmt = $pdo->prepare("SELECT v.id, v.vote_hash, v.block_index, v.merkle_root, v.nonce, v.timestamp 
                           FROM votes v WHERE v.vote_receipt = ?");
    $stmt->execute([$receiptToken]);
    $vote = $stmt->fetch();

    if ($vote) {
        // Get Merkle proof for this vote
        $proof = getMerkleProof($vote['vote_hash'], $merkleInfo['tree']);
        $proofValid = $proof !== false && verifyMerkleProof($vote['vote_hash'], $proof, $merkleInfo['root']);

        $receiptResult = [
            'found' => true,
            'block_index' => $vote['block_index'] ?? $vote['id'],
            'timestamp' => $vote['timestamp'],
            'vote_hash' => $vote['vote_hash'],
            'merkle_root' => $vote['merkle_root'] ?? $merkleInfo['root'],
            'proof_valid' => $proofValid,
            'proof' => $proof ?: [],
        ];

        // Audit log (only if pdo available)
        logAuditEvent(
            $pdo,
            AUDIT_CHAIN_VERIFY,
            ACTOR_SYSTEM,
            null,
            'Receipt verification: ' . substr($receiptToken, 0, 16) . '... — ' . ($vote ? 'FOUND' : 'NOT FOUND')
        );
    } else {
        $receiptResult = ['found' => false];
    }
}

// Get candidate totals (public info)
$candidateTotals = $pdo->query("
    SELECT c.name, c.party, c.symbol, COUNT(v.id) as vote_count
    FROM candidates c
    LEFT JOIN votes v ON c.id = v.candidate_id
    GROUP BY c.id
    ORDER BY vote_count DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Vote Verification - VoteUnity</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .verify-hero {
            text-align: center;
            padding: 2rem 0;
        }

        .verify-hero h1 {
            font-size: 2rem;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .ledger-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }

        .ledger-stat {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 1.25rem;
            text-align: center;
        }

        .ledger-stat .value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #a5b4fc;
        }

        .ledger-stat .label {
            font-size: 0.85rem;
            color: var(--gray);
            margin-top: 0.25rem;
        }

        .merkle-root-box {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid #10b981;
            border-radius: 12px;
            padding: 1.25rem;
            text-align: center;
            margin: 1.5rem 0;
        }

        .merkle-root-box code {
            background: rgba(0, 0, 0, 0.3);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            display: inline-block;
            word-break: break-all;
            font-size: 0.8rem;
            color: #10b981;
            max-width: 100%;
        }

        .receipt-form {
            max-width: 600px;
            margin: 0 auto;
        }

        .receipt-input {
            display: flex;
            gap: 0.5rem;
        }

        .receipt-input input {
            flex: 1;
        }

        .proof-step {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
        }

        .proof-step .direction {
            background: #6366f1;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            text-transform: uppercase;
            flex-shrink: 0;
        }

        .proof-step code {
            font-size: 0.75rem;
            word-break: break-all;
            color: #94a3b8;
        }

        .result-card {
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }

        .result-success {
            background: rgba(16, 185, 129, 0.1);
            border: 2px solid #10b981;
        }

        .result-fail {
            background: rgba(239, 68, 68, 0.1);
            border: 2px solid #ef4444;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="nav-brand">
            <a href="<?= BASE_URL ?>/">
                <span class="nav-icon">🗳️</span>
                <span class="nav-title">VoteUnity</span>
            </a>
        </div>
        <div class="nav-links">
            <a href="<?= BASE_URL ?>/">Home</a>
            <a href="<?= BASE_URL ?>/pages/verify.php" class="active" style="color: #a855f7;">🔍 Verify</a>
            <a href="<?= BASE_URL ?>/pages/login.php">🔑 Login</a>
        </div>
    </nav>

    <main class="container">
        <div class="verify-hero">
            <h1>🔍 Public Election Verification</h1>
            <p style="color: var(--gray); max-width: 600px; margin: 0 auto;">
                Transparent and verifiable. Check election integrity and verify your vote was counted — without
                revealing your identity.
            </p>
        </div>

        <!-- Ledger Stats -->
        <div class="ledger-grid">
            <div class="ledger-stat">
                <div class="value">
                    <?= $ledgerInfo['votes_cast'] ?>
                </div>
                <div class="label">Total Votes Cast</div>
            </div>
            <div class="ledger-stat">
                <div class="value">
                    <?= $ledgerInfo['total_voters'] ?>
                </div>
                <div class="label">Registered Voters</div>
            </div>
            <div class="ledger-stat">
                <div class="value">
                    <?= $ledgerInfo['total_candidates'] ?>
                </div>
                <div class="label">Candidates</div>
            </div>
            <div class="ledger-stat">
                <div class="value" style="color: <?= $ledgerInfo['chain_valid'] ? '#10b981' : '#ef4444' ?>;">
                    <?= $ledgerInfo['chain_valid'] ? '✓ VALID' : '✗ BROKEN' ?>
                </div>
                <div class="label">Chain Integrity</div>
            </div>
        </div>

        <!-- Merkle Root -->
        <div class="merkle-root-box">
            <p style="margin-bottom: 0.5rem; font-weight: 600;">🌳 Current Merkle Root</p>
            <code><?= htmlspecialchars($merkleInfo['root']) ?></code>
            <p style="color: var(--gray); font-size: 0.8rem; margin-top: 0.5rem;">
                This cryptographic fingerprint represents all
                <?= $merkleInfo['count'] ?> votes combined.
                Any change to any vote would produce a different root.
            </p>
        </div>

        <!-- Vote Distribution (Public) -->
        <div class="card" style="margin-bottom: 2rem;">
            <h3 style="margin-bottom: 1rem;">📊 Vote Distribution</h3>
            <?php if (empty($candidateTotals) || $ledgerInfo['votes_cast'] == 0): ?>
                <p style="color: var(--gray);">No votes have been cast yet.</p>
            <?php else: ?>
                <?php foreach ($candidateTotals as $c):
                    $pct = $ledgerInfo['votes_cast'] > 0 ? ($c['vote_count'] / $ledgerInfo['votes_cast']) * 100 : 0;
                    ?>
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.75rem;">
                        <div style="width: 130px; flex-shrink: 0;">
                            <strong>
                                <?= htmlspecialchars($c['name']) ?>
                            </strong><br>
                            <small style="color: var(--gray);">
                                <?= htmlspecialchars($c['party']) ?>
                            </small>
                        </div>
                        <div
                            style="flex: 1; background: rgba(255,255,255,0.1); height: 24px; border-radius: 4px; overflow: hidden;">
                            <div
                                style="width: <?= $pct ?>%; height: 100%; background: linear-gradient(90deg, #6366f1, #a855f7); transition: width 0.5s;">
                            </div>
                        </div>
                        <div style="width: 80px; text-align: right;">
                            <strong>
                                <?= $c['vote_count'] ?>
                            </strong>
                            <small style="color: var(--gray);"> (
                                <?= round($pct, 1) ?>%)
                            </small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Receipt Verification -->
        <div class="card">
            <h3 style="margin-bottom: 1rem;">🧾 Verify Your Vote Receipt</h3>
            <p style="color: var(--gray); margin-bottom: 1rem; font-size: 0.9rem;">
                Enter your vote receipt token to confirm your vote was counted and included in the Merkle tree.
                Your identity and candidate choice are NOT revealed.
            </p>

            <form method="POST" action="" class="receipt-form">
                <div class="receipt-input">
                    <input type="text" name="receipt" class="form-control"
                        placeholder="Paste your vote receipt token here..."
                        value="<?= htmlspecialchars($receiptToken) ?>" required>
                    <button type="submit" class="btn btn-primary">Verify</button>
                </div>
            </form>

            <?php if ($receiptResult !== null): ?>
                <?php if ($receiptResult['found']): ?>
                    <div class="result-card result-success">
                        <div style="text-align: center; margin-bottom: 1rem;">
                            <span style="font-size: 3rem;">✅</span>
                            <h3 style="color: #10b981; margin-top: 0.5rem;">Vote Confirmed!</h3>
                            <p style="color: var(--gray);">Your vote is recorded in the immutable ledger and verified by the
                                Merkle tree.</p>
                        </div>

                        <div style="display: grid; gap: 0.5rem; font-size: 0.9rem;">
                            <div><strong>Block #:</strong>
                                <?= $receiptResult['block_index'] ?>
                            </div>
                            <div><strong>Timestamp:</strong>
                                <?= date('M j, Y g:i:s A', strtotime($receiptResult['timestamp'])) ?>
                            </div>
                            <div><strong>Vote Hash:</strong> <code
                                    style="font-size: 0.75rem; word-break: break-all;"><?= $receiptResult['vote_hash'] ?></code>
                            </div>
                            <div><strong>Merkle Root:</strong> <code
                                    style="font-size: 0.75rem; word-break: break-all;"><?= $receiptResult['merkle_root'] ?></code>
                            </div>
                            <div><strong>Merkle Proof:</strong>
                                <span
                                    style="color: <?= $receiptResult['proof_valid'] ? '#10b981' : '#ef4444' ?>; font-weight: 600;">
                                    <?= $receiptResult['proof_valid'] ? '✓ Valid' : '✗ Invalid' ?>
                                </span>
                            </div>
                        </div>

                        <?php if (!empty($receiptResult['proof'])): ?>
                            <div style="margin-top: 1rem;">
                                <p style="font-weight: 600; margin-bottom: 0.5rem; font-size: 0.85rem;">Merkle Proof Path:</p>
                                <?php foreach ($receiptResult['proof'] as $i => $step): ?>
                                    <div class="proof-step">
                                        <span>Step
                                            <?= $i + 1 ?>
                                        </span>
                                        <span class="direction">
                                            <?= $step['position'] ?>
                                        </span>
                                        <code><?= substr($step['hash'], 0, 24) ?>...</code>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
                            <p style="color: var(--gray); font-size: 0.8rem;">
                                ℹ️ <strong>Privacy note:</strong> Your identity and vote choice are NOT shown here.
                                Only the cryptographic proof that your vote exists in the ledger is displayed.
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="result-card result-fail">
                        <div style="text-align: center;">
                            <span style="font-size: 3rem;">❌</span>
                            <h3 style="color: #ef4444; margin-top: 0.5rem;">Receipt Not Found</h3>
                            <p style="color: var(--gray); margin-top: 0.5rem;">
                                No vote matches this receipt token. Please double-check your receipt and try again.
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- How it Works -->
        <div class="card" style="margin-top: 2rem;">
            <h3 style="margin-bottom: 1rem;">🔐 How Verification Works</h3>
            <div style="display: grid; gap: 1rem; color: var(--gray); font-size: 0.9rem; line-height: 1.7;">
                <div>
                    <strong style="color: #a5b4fc;">1. Immutable Ledger</strong><br>
                    Each vote is stored as a block with a unique hash that depends on the previous vote's hash, creating
                    an unbreakable chain. Any tampering breaks the chain.
                </div>
                <div>
                    <strong style="color: #a5b4fc;">2. Merkle Tree</strong><br>
                    All vote hashes are combined into a binary tree. The root hash is a compact fingerprint of ALL
                    votes. Your receipt lets you prove inclusion without exposing other votes.
                </div>
                <div>
                    <strong style="color: #a5b4fc;">3. AES-256-GCM Encryption</strong><br>
                    Vote data is encrypted at rest using military-grade encryption. Even database administrators cannot
                    read raw vote contents.
                </div>
                <div>
                    <strong style="color: #a5b4fc;">4. Vote Receipt</strong><br>
                    A unique, randomly generated token given to each voter. It can be used to verify the vote was
                    counted without revealing who you voted for.
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-content">
            <p>🔒 VoteUnity — Secure, Transparent, Verifiable Elections</p>
        </div>
    </footer>
</body>

</html>