<?php
$options;
$authorizedEmails;
$responses;

// renders frontpage
$app->get('/', function () use ($app) {
    $app->render(
        "frontpage.html",
        array(
            "options"=>getOptions()
        )
    );
})->name('index.index');

// renders admin panel
$app->get('/admin', function () use ($app) {
    $app->render(
        "admin.html",
        array(
            "table"=>getRegisterTable(),
            "emails"=>implode("\n",getAuthorizedEmails()),
            "slots"=>getSlots()
        )
    );
})->name('index.admin');

// [admin] deletes a selected slot
$app->get('/deletesubmit', function () use ($app) {
    $dates = array_keys($app->request->post('date'));
    if (count($dates) > 0) {
        $db = Database::singleton();
        $escapedDates = array();
        for ($i = 0; $i < count($dates); $i++) {
            $d = $dates[$i];
            $db->real_escape_string($d);
            array_push($escapedDates, $d);
        }
        $queryPart = "'" . implode("', '", $escapedDates) . "'";
        $db->query("DELETE FROM responses WHERE pickedDate in ($queryPart)");
    }

    $app->redirectTo('index.admin');
})->name('index.admin.deletesubmit')->via('POST');

// [admin] updates the authorized emails, does not check if a removed email has already selected a slot.
$app->get('/changeauthorizedemails', function () use ($app) {
    $emails = $app->request->post('emails');
    $emails = explode("\n",$emails);
    if (count($emails) > 0) {
        $processed = array();
        $db = Database::singleton();
        foreach ($emails as $email) {
            $e = trim($email);
            $db->real_escape_string($e);
            array_push($processed, $e);
        }

        $queryPart = "('" . implode("'),('", $processed) . "')";
        $db->query("TRUNCATE TABLE authorizedEmails;");
        $db->query("INSERT INTO authorizedEmails VALUES $queryPart;");
    }

    $app->redirectTo('index.admin');
})->name('index.admin.authorizedemails')->via('POST');

// [admin] updates the authorized emails, does not check if a removed email has already selected a slot.
$app->get('/updatesettings', function () use ($app) {
    $settings = $app->request->post();
    $settingKeys = array_keys($settings);
    $db = Database::singleton();
    for ($i = 0; $i < count($settingKeys); $i++) {
        $key = $settingKeys[$i];
        $value = $settings[$key];
        $db->real_escape_string($key);
        $db->real_escape_string($value);
        $db->query("INSERT INTO settings VALUES ('$key', '$value') ON DUPLICATE KEY UPDATE value = '$value'");
    }
    $app->redirectTo('index.admin');
})->name('index.admin.settings')->via('POST');

// [admin] updates the slots, if there are people who already selected a slot that's being removed, doesn't touch those entries.
$app->get('/changeslots', function () use ($app) {
    $slots = $app->request->post('slots');
    $slots = explode("\n", $slots);
    if (count($slots) > 0) {
        $processed = array();
        $db = Database::singleton();
        foreach ($slots as $slot) {
            $slot = trim($slot);
            $list = explode(";", $slot);

            if (!count($list) == 2) {
                $app->flash('error',"The format of the slot is incorrect when processing this line: " . $slot);
            } else {
                list($name, $capacity) = $list;

                if (strlen($name) == 0) {
                    $app->flash('error', "The name must be at least 1 character long when processing this line: " . $slot);
                } else {
                    $db->real_escape_string($name);
                    $capacity = intval($capacity);
                    array_push($processed, "'$name', $capacity");
                }
            }
        }
        $queryPart = "(" . implode("),(", $processed) . ")";
        $db->query("TRUNCATE TABLE slots;");
        $db->query("INSERT INTO slots (name, capacity) VALUES $queryPart;");
    }
    $app->redirectTo('index.admin');
})->name('index.admin.slots')->via('POST');

// if an user selects a slot this is the confirmation page.
$app->get('/success', function () use ($app) {
    $app->render(
        "success.html",
        array()
    );
})->name('index.success');

// login UI for admin
$app->get('/login', function () use ($app) {
    $app->render(
        "login.html",
        array()
    );
})->name('index.login');

// logs out the admin
$app->get('/logout', function () use ($app) {
    unset($_SESSION['user']);
    $app->redirectTo('index.index');
})->name('index.logout');

// checks for login for the admin
$app->get('/loginauth', function () use ($app) {
    $password= $app->request->post('password');
    if ($password == ADMIN_ACCOUNT_PASSWORD)
    {
        $_SESSION['user'] = "admin";
        $app->redirect($_SESSION['urlRedirect']);
    }

    $app->redirectTo('index.login');
})->name('index.admin.login')->via('POST');

// submits users' date selection, checks if that user has already selected a date and errors if it's the case.
$app->get('/formsubmit', function () use ($app) {
    $success = false;
    $settings = Model::getSettings();
    
    if ($app->request->post('pin') == '10001')
    {
        $name = $app->request->post('name');
        $email = strtolower($app->request->post('email'));
        $pickedDate = $app->request->post('dates');

        $app->flash('refill.name', $name);
        $app->flash('refill.email', $email);
        $app->flash('refill.pickedDate', $pickedDate);
        $authorizedEmails = getAuthorizedEmails();
        $isAuthorizedEmail = array_search($email, $authorizedEmails);
        $db = Database::singleton();
        $db->real_escape_string($name);
        $db->real_escape_string($email);
        $db->real_escape_string($pickedDate);
        
        if ($isAuthorizedEmail === false) {
            $app->flash('error',"Sorry, $email is not one of the authorized emails to use this site, contact <a href='mailto:" . $settings['contactEmail'] . "'>" . $settings['contactName'] . "</a>.");
        } else {
            $count = $db->querySingle("SELECT COUNT(*) FROM responses WHERE email = '$email'");

            if ($count > 0) {
                $pickedDate = $db->querySingle("SELECT pickedDate FROM responses WHERE email = '$email'");
                $app->flash('error',"You have already picked $pickedDate before. If you want to delete it, contact <a href='mailto:" . $settings['contactEmail'] . "'>" . $settings['contactName'] . "</a>.");
            } else {
                $db->query("INSERT INTO responses (name, email, pickedDate) VALUES ('$name', '$email', '$pickedDate')");
                $success = true;
            }
        }
    }
    else
    {
        $app->flash('error', "Pin is incorrect. Contact <a href='mailto:" . $settings['contactEmail'] . "'>" . $settings['contactName'] . "</a> to learn the pin.");
    }

    if ($success)
    {
        $app->redirectTo('index.success');
    }

    $app->redirectTo('index.index');
})->name('index.formsubmit')->via('POST');

function setup()
{
    global $authorizedEmails, $options, $responses;
    $db = Database::singleton();
    $responses = $db->fetch_full_result_array("SELECT pickedDate, COUNT(*) AS count FROM responses GROUP BY pickedDate");
    $options = $db->fetch_full_result_array("SELECT name, capacity, capacity as originalcapacity FROM slots ORDER BY id");
    foreach ($responses as $resp)
    {
        for($i = 0; $i< count($options); $i++)
        {
            if ($options[$i]["name"] == $resp["pickedDate"])
            {
                $options[$i]["capacity"] -= $resp["count"];
            }
        }
    }
}

function getSlots()
{
    $opt = getOptions();
    $ret = "";
    foreach ($opt as $o)
    {
        $ret .= $o["name"] . ";" . $o["originalcapacity"] . "\n";
    }

    return $ret;
}

function getAuthorizedEmails()
{
    global $authorizedEmails;
    if (!$authorizedEmails)
    {
        $db = Database::singleton();
        $result = $db->query("SELECT email FROM authorizedEmails ORDER BY email;");
        while($row = $db->fetch_assoc($result)) {
            $authorizedEmails[] = $row["email"];
        }
    }

    return $authorizedEmails;
}

function getOptions()
{
    global $options;
    if (!$options) setup();
    return $options;
}

function getRegisterTable()
{
    $db = Database::singleton();
    $responseTable = $db->fetch_full_result_array("SELECT pickedDate, name, email FROM responses ORDER BY pickedDate, name");
    $options = getOptions();
    $table = array();
    for($i = 0; $i < count($options); $i++)
    {
        $optionCount = intval($options[$i]["originalcapacity"]);
        foreach ($responseTable as $resp)
        {
            if ($options[$i]["name"] == $resp["pickedDate"])
            {
                $optionCount --;
                array_push($table, array('date' => $options[$i]["name"], 'name' => $resp["name"], 'email' => $resp["email"]));
            }
        }
        for ($o = 0; $o < $optionCount; $o++)
        {
            array_push($table, array('date' => $options[$i]["name"], 'name' => '', 'email' => ''));
        }
    }

    return $table;
}