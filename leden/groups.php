<?php
// Fixed scouting sections, matching the public website. Database IDs stay unchanged.
function ledenFixedGroupNames() {
    return ['Bevers','Welpen Klavertje 4','Welpen Sionie Horde','Welpen Nishaani Horde','Scouts de Dwaalsterren','Neil Armstrong Troep','Explorers','S5 Stam','+Scouts'];
}
function ledenFixedGroups($database) {
    $rows = $database->query('SELECT id,name FROM ledenadmin_groups')->fetch_all(MYSQLI_ASSOC);
    $byName = [];
    foreach ($rows as $row) $byName[$row['name']] = $row;
    $groups = [];
    foreach (ledenFixedGroupNames() as $name) {
        if (!isset($byName[$name])) {
            // IGNORE handles simultaneous first visits without changing existing IDs or names.
            $stmt = $database->prepare('INSERT IGNORE INTO ledenadmin_groups(name) VALUES (?)');
            $stmt->bind_param('s', $name);$stmt->execute();$stmt->close();
            $stmt = $database->prepare('SELECT id,name FROM ledenadmin_groups WHERE name=?');
            $stmt->bind_param('s', $name);$stmt->execute();
            $byName[$name] = $stmt->get_result()->fetch_assoc();$stmt->close();
        }
        $group = $byName[$name];
        $group['id'] = (int)$group['id'];
        $groups[] = $group;
    }
    return $groups;
}
