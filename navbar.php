<?php
// navbar.php
?>
<div class="container">
    <div class="navbar navbar-inverse">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand visible-xs visible-sm" href="<?php echo($mybaseurl); ?>/index.php" title="My Timecard">MTR</a>
            <a class="navbar-brand visible-md visible-lg" href="<?php echo($mybaseurl); ?>/index.php">My Timecard</a>
        </div>
        <div class="collapse navbar-collapse">
            <ul class="nav navbar-nav">
                <li><a href="<?php echo($mybaseurl); ?>/index.php"><?php echo($user['user_firstname'].' '.$user['user_lastname']); ?></a></li>
                <?php
                $team_params = [
                        'uid'        => $loggedInUser ?? '',
                        'mode'       => $_GET['mode']       ?? 'balanced',
                        'start'      => $_GET['start']      ?? date('Y-m-01'),
                        'end'        => $_GET['end']        ?? date('Y-m-d'),
                        'quickRange' => $_GET['quickRange'] ?? 'thisMonth',
                ];
                if ($user['user_is_admin']) {
                    $all_params = $team_params;
                    $all_params['team'] = 'all';
                    $allUsersUrl = $mybaseurl . '/team_users.php?' . http_build_query($all_params);
                    echo('<li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown">Admin <b class="caret"></b></a>');
                    echo('<ul class="dropdown-menu">');
                    echo('<li><a href="'.$allUsersUrl.'">All Users</a></li>');
                    echo('</ul>');
                    echo('</li>');
                }
                if ($user['user_is_admin'] || $user['user_is_supervisor']) {
                    // Direct team
                    $direct_params = $team_params;
                    $direct_params['team'] = 'direct';
                    $directTeamUrl = $mybaseurl . '/team_users.php?' . http_build_query($direct_params);

                    // Recursive team
                    $recursive_params = $team_params;
                    $recursive_params['team'] = 'recursive';
                    $recursiveTeamUrl = $mybaseurl . '/team_users.php?' . http_build_query($recursive_params);

                    echo('<li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown">Supervisor <b class="caret"></b></a>');
                    echo('<ul class="dropdown-menu">');
                    echo('<li><a href="'.$directTeamUrl.'">Direct Team</a></li>');
                    echo('<li><a href="'.$recursiveTeamUrl.'">Full Team (Recursive)</a></li>');
                    echo('</ul>');
                    echo('</li>');
                }
                ?>

                <li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown">Links <b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <li><a target="_blank" href="https://jireh.smarteru.com/remote-login/login.cfm">SmarterU</a></li>
                        <li><a target="_blank" href="http://hilwiki.jfab.aosmd.com">HilWiki</a></li>
                        <li><a href="https://portal.aosmd.com" target="_blank">Portal</a></li>
                        <li class="divider"></li>
                        <li><a target="_blank" href="http://webx.jfab.aosmd.com/tcs">TCS</a></li>
                        <li><a href="http://web11.jfab.aosmd.com/mts" target="_blank">6S</a></li>
                        <li><a href="http://webx.jfab.aosmd.com/SPCx" target="_blank">SPCx</a></li>
                        <li><a href="http://jfabieapp1.jfab.aosmd.com/tps" target="_blank">TPS</a></li>
                        <li><a href="http://jfabjunoapp.jfab.aosmd.com/juno/juno_data_search/" target="_blank">Juno</a></li>
                    </ul>
                </li>
            </ul>
        </div><!--/.nav-collapse -->
    </div>
</div>
