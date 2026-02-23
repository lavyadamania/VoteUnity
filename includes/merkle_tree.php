<?php
/**
 * Merkle Tree Implementation for Vote Verification
 * 
 * Provides cryptographic proof that a specific vote is included
 * in the election results without revealing other votes.
 */

/**
 * Hash two nodes together (internal Merkle node)
 */
function merkleHash($left, $right)
{
    // Sort to ensure consistent ordering
    if ($left > $right) {
        list($left, $right) = [$right, $left];
    }
    return hash('sha256', $left . $right);
}

/**
 * Build a Merkle tree from an array of leaf hashes
 *
 * @param array $leaves — array of SHA-256 hash strings
 * @return array        — ['root' => string, 'levels' => array of arrays]
 */
function buildMerkleTree($leaves)
{
    if (empty($leaves)) {
        return ['root' => hash('sha256', 'EMPTY'), 'levels' => [[]]];
    }

    if (count($leaves) === 1) {
        return ['root' => $leaves[0], 'levels' => [$leaves]];
    }

    $levels = [$leaves]; // Level 0 = leaf nodes
    $currentLevel = $leaves;

    while (count($currentLevel) > 1) {
        $nextLevel = [];

        for ($i = 0; $i < count($currentLevel); $i += 2) {
            if ($i + 1 < count($currentLevel)) {
                $nextLevel[] = merkleHash($currentLevel[$i], $currentLevel[$i + 1]);
            } else {
                // Odd number: duplicate the last node
                $nextLevel[] = merkleHash($currentLevel[$i], $currentLevel[$i]);
            }
        }

        $levels[] = $nextLevel;
        $currentLevel = $nextLevel;
    }

    return [
        'root' => $currentLevel[0],
        'levels' => $levels
    ];
}

/**
 * Get the Merkle proof for a specific leaf hash
 *
 * @param string $leafHash — the vote hash to prove
 * @param array  $tree     — the tree returned by buildMerkleTree()
 * @return array|false     — array of {hash, position} pairs, or false if leaf not found
 */
function getMerkleProof($leafHash, $tree)
{
    $levels = $tree['levels'];
    $index = array_search($leafHash, $levels[0]);

    if ($index === false) {
        return false;
    }

    $proof = [];

    for ($level = 0; $level < count($levels) - 1; $level++) {
        $currentLevel = $levels[$level];
        $isRight = ($index % 2 === 1);
        $siblingIndex = $isRight ? $index - 1 : $index + 1;

        if ($siblingIndex < count($currentLevel)) {
            $proof[] = [
                'hash' => $currentLevel[$siblingIndex],
                'position' => $isRight ? 'left' : 'right'
            ];
        } else {
            // Odd node duplicated — sibling is itself
            $proof[] = [
                'hash' => $currentLevel[$index],
                'position' => $isRight ? 'left' : 'right'
            ];
        }

        $index = intdiv($index, 2);
    }

    return $proof;
}

/**
 * Verify a Merkle proof
 *
 * @param string $leafHash   — the vote hash
 * @param array  $proof      — the proof from getMerkleProof()
 * @param string $merkleRoot — the expected root hash
 * @return bool
 */
function verifyMerkleProof($leafHash, $proof, $merkleRoot)
{
    $currentHash = $leafHash;

    foreach ($proof as $step) {
        if ($step['position'] === 'left') {
            $currentHash = merkleHash($step['hash'], $currentHash);
        } else {
            $currentHash = merkleHash($currentHash, $step['hash']);
        }
    }

    return $currentHash === $merkleRoot;
}

/**
 * Compute the current Merkle root from all votes in the database
 *
 * @param PDO $pdo — database connection
 * @return array   — ['root' => string, 'tree' => full tree, 'count' => int]
 */
function computeMerkleRoot($pdo)
{
    $stmt = $pdo->query("SELECT vote_hash FROM votes ORDER BY id ASC");
    $hashes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $tree = buildMerkleTree($hashes);

    return [
        'root' => $tree['root'],
        'tree' => $tree,
        'count' => count($hashes)
    ];
}

/**
 * Get a compact Merkle tree visualization (for admin UI)
 *
 * @param array $tree — the tree from buildMerkleTree()
 * @return array      — array of level info for display
 */
function getMerkleTreeVisualization($tree)
{
    $viz = [];

    for ($i = count($tree['levels']) - 1; $i >= 0; $i--) {
        $levelName = $i === count($tree['levels']) - 1 ? 'Root' :
            ($i === 0 ? 'Leaves (Vote Hashes)' : 'Level ' . $i);
        $viz[] = [
            'name' => $levelName,
            'hashes' => $tree['levels'][$i],
            'count' => count($tree['levels'][$i])
        ];
    }

    return $viz;
}
?>