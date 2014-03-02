<?php  if (!defined('BASEPATH')) exit('No direct script access allowed'); ?>
<h1>CTC Database</h1>
Welcome to the CTC database. Choose a menu item from the left to continue.

<h2>Notes on membership figures for annual report</h2>
<p>To get data for the annual report:</p>
<ol>
    <li>Use the <em>List Members by type</em> query to get the current membership
        broken down by membership type. Record the number of each type relevant
        to the report (which doesn't distinguish ordinary memberships from
        couple memberships, etc).</li>
    <li>Use the <em>New members this year</em> query to find how many new
        members have joined since the start of the current membership year
        (and so should not appear in the report). Subtract these numbers from
        those of step (1).</li>
    <li>Use the <em>Resignations last year</em> query to get all resignations
        since the last annual report. Record the number of each type.</li>
    <li>Use the <em>New members last year</em> query to get all new members since
        the last report. Record the numbers of each type.</li>
    <li>Use the <em>MS type changes</em> query to find any members whose
        membership type as changed, e.g. from Junior to Ordinary or Ordinary
        to Associate. Select only those relevant to the year of interest and
        record each as -1 and +1 in the 'old' and 'new' membership types
        respectively.</li>
</ol>
<p>All being well the numbers from steps (3) through (4), when
    added to the membership numbers at the end of the previous membership
    year (given in the previous year's annual report), should
    agree with the numbers from steps (1) and (2). Good luck!
</p>

