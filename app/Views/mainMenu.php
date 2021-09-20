<?php

// This module contains the code to define the main home-page menus

helper('url');

function menuItem($uriSegs, $text, $title, $newWindow = false, $isAbsolute = false) {
    // Generate a menu item for the given uri (which is a CI relative URI segments
    // unless $isAbsolute is true) with given link text and title. target will be
    // set to _blank if $newWindow is true.
    echo "<tr align='left'><td>";
    $attribs['class']="menuItem";
    $attribs['title'] = $title;
    if ($newWindow) {
        $attribs['target'] = "_blank";
    }
    if ($isAbsolute) {
        echo "<a href=\"$uriSegs\"";
        foreach ($attribs as $attrName => $attr) {
            echo " $attrName=\"$attr\"";
        }
        echo ">$text</a>";
    }
    else {
        echo anchor($uriSegs, $text, $attribs);
        }
    echo "</td></tr>";
}

function menuSubhead($text) {
    echo "<tr align='left'><td class='MenuSubhead'>$text</td></tr>";
}

function firstSentence($s) {
    // The first sentence of the given description.
    $fullStopPos = strpos($s,".");
    if ($fullStopPos === FALSE) {
        return $s;
    }
    else {
        return substr($s,0,$fullStopPos + 1);
    }
}

function homeMenu($joomlaBaseURL) {
    // Display the main menu 'home' and 'user queries' bits
    menuItem("$joomlaBaseURL", "CTC Home", "Return to the main CTC website", false, true);
    menuItem("", "DB Home", "Return to the CTCDB Home Page");
    menuItem("queries/manageQueries", "Run/edit user queries", "Manage user-defined queries");
    menuItem("document", "Document management", "Manage documents used in queries");
}

function membershipMenu() {
    // Display the menu of membership management controls (only to
    // users with full db access).
    if (session()->hasFullAccess) {
        menuSubhead("Membership changes");
        menuItem("ctc/editMember", "View/edit member", "View or edit a member's details");
        menuItem("ctc/newMember", "New member", "Add a new (single) member to the database");
        menuItem("ctc/newCouple", "New couple", "Add a new couple to the database");
        menuItem("ctc/coupleMembers", "Couple members", "Combine two single memberships into a couple membership");
        menuItem("ctc/decoupleMembers", "Decouple members", "Split a couple membership into two single memberships");
        menuItem("ctc/closeMembership", "Close membership", "Close a membership");
        menuItem("ctc/reinstateMembership", "Reinstate membership", "Undo a previous membership closure");
        menuItem("ctc/rejoinMember", "Rejoin member", "Open a new membership for a prior member stored in the database");
        menuItem("ctc/setPassword","Set password", "Set a new password for an existing member");
        menuItem("ctc/manageRoles","Manage roles", "Manage official member roles (committee etc)");
    }
}

function queriesMenu() {
    // Display the standard "system" queries (i.e., all the user-queries owned by memberId = 0)
    // plus a link to the special Envelope Printing query builder.
    menuSubhead("Queries (new window)");
    $queryList = model('CTCModel')->getQueries(0);
    foreach ($queryList as $query) {
        $id = $query['id'];
        $link = site_url("queries/runQuery/$id");
        $label = str_replace('_',' ',$query['name']); // Make name into a menu label
        menuItem($link, $label, firstSentence($query['description']), true);
    }
    menuItem(site_url("queries/printEnvelopes"), "Print Envelopes", "Query builder for envelope printing");
}

function subsMenu($joomlaBaseURL) {
    // Display the menu of subscriptions-related commands.
    menuSubhead("Subscriptions");
    if (session()->hasFullAccess) {
        menuItem("subs/recordPayments", "Enter/edit payments", "Enter or edit subscription payments", true);
        menuItem("subs/deletePayment", "Delete payment", "Delete an existing subscription payment (e.g. if recorded in error)");
    }
    menuItem($joomlaBaseURL."/newsletter/generate.php?expand=ctcsubsnotice.odt", "Print Invoices",
        "Subs invoices for all unpaid memberships", false, true);
}

// Now the actual "main" code for displaying the menu.
// ===================================================
echo "<h3>Main Menu</h3>";
echo '<table class="menu" width="100%" border="0" cellpadding="0" cellspacing="0">';
homeMenu($joomlaBaseURL);
membershipMenu();
queriesMenu();
subsMenu($joomlaBaseURL);
echo "</table>";
echo "<hr />";

$userName = session()->name;
$login = session()->login;
$roles = session()->roles;
echo "<table>";
echo "<tr><td class=\"menuItem\"><h3>Current User</h3></td></tr>";
echo "<tr><td class=\"menuItem\">{$userName}</td></tr>";
echo "<tr><td class=\"menuItem\">({$login})</td></tr>";
foreach ($roles as $role) {
    echo "<tr><td class=\"menuItem\">$role</td></tr>";
}
echo "</table>"

?>

