<!DOCTYPE html>
<html lang="en">
    <head>        
        <!-- META SECTION -->
        <title><a:page_title textonly="1"></title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        
        <link rel="icon" href="favicon.ico" type="image/x-icon" />
        <!-- END META SECTION -->
        
        <!-- CSS INCLUDE -->        
        <link rel="stylesheet" type="text/css" id="theme" href="~theme_uri~/css/theme-default.css?v=8"/>
        <!-- EOF CSS INCLUDE -->                                      
    </head>
    <body>
        <!-- START PAGE CONTAINER -->
        <div class="page-container">
            
            <!-- START PAGE SIDEBAR -->
            <div class="page-sidebar">
                <!-- START X-NAVIGATION -->
                <ul class="x-navigation">
                    <li class="xn-logo">
                        <a href="/admin/index">ATLANT</a>
                        <a href="#" class="x-navigation-control"></a>
                    </li>
                    <li class="xn-profile">
                        <a href="#" class="profile-mini">
                            <img src="~theme_uri~/assets/images/users/avatar.jpg" alt="~profile.full_name~"/>
                        </a>
                        <div class="profile">
                            <div class="profile-image">
                                <img src="~theme_uri~/assets/images/users/avatar.jpg" alt="~profile.full_name~"/>
                            </div>
                            <div class="profile-data">
                                <div class="profile-data-name">~profile.full_name~ (~profile.username~)</div>
                            </div>
                            <div class="profile-controls">
                                <a href="/admin/settings/admin_manage?admin_id=~profile.id~" class="profile-control-left"><span class="fa fa-info"></span></a>
                                <a href="/admin/notifications" class="profile-control-right"><span class="fa fa-envelope"></span></a>
                            </div>
                        </div>                                                                        
                    </li>
                    <li class="xn-title">Navigation</li>
                    <a:if ~userid~ != 0>
                        <a:nav_menu>
                    </a:if>
                </ul>
                <!-- END X-NAVIGATION -->
            </div>
            <!-- END PAGE SIDEBAR -->
            
            <!-- PAGE CONTENT -->
            <div class="page-content">
                
                <!-- START X-NAVIGATION VERTICAL -->
                <ul class="x-navigation x-navigation-horizontal x-navigation-panel">
                    <!-- TOGGLE NAVIGATION -->
                    <li class="xn-icon-button">
                        <a href="#" class="x-navigation-minimize"><span class="fa fa-dedent"></span></a>
                    </li>
                    <!-- END TOGGLE NAVIGATION -->
                    <!--SEARCH -->
                    <li class="xn-search">
                        <form role="form">
                            <input type="text" name="search" placeholder="Search..."/>
                        </form>
                    </li>   
                    <!-- END SEARCH -->                    
                 
                    <!-- POWER OFF -->
                    <li class="xn-icon-button pull-right last">
                        <a href="#"><span class="fa fa-power-off"></span></a>
                        <ul class="xn-drop-left animated zoomIn">
                            <li><a href="/admin/logout" class="mb-control" data-box="#mb-signout"><span class="fa fa-sign-out"></span> Sign Out</a></li>
                        </ul>                        
                    </li> 
                    <!-- END POWER OFF -->  


                    <!-- MESSAGES -->
                    <li class="xn-icon-button pull-right">
                        <a href="#"><span class="fa fa-comments"></span></a>
                        <div class="informer informer-danger">0</div>
                        <div class="panel panel-primary animated zoomIn xn-drop-left xn-panel-dragging">
                            <div class="panel-heading">
                                <h3 class="panel-title"><span class="fa fa-comments"></span> Messages</h3>                                
                                <div class="pull-right">
                                    <span class="label label-danger"></span>
                                </div>
                            </div>
                            <div class="panel-body list-group list-group-contacts scroll" style="height: 200px;">
                               <a:dropdown_messages>
                            </div>     
                            <div class="panel-footer text-center">
                                <a href="/admin/inbox">Show all messages</a>
                            </div>                            
                        </div>                        
                    </li>
                    <!-- END MESSAGES -->



                    
                    <!-- TASKS -->
                    <li class="xn-icon-button pull-right">
                        <a href="#"><span class="fa fa-tasks"></span></a>
                        <div class="panel panel-primary animated zoomIn xn-drop-left xn-panel-dragging">
                            <div class="panel-heading">
                                <h3 class="panel-title"><span class="fa fa-tasks"></span> Alerts</h3>                                
                            </div>
                            <div class="panel-body list-group scroll" style="height: 200px;">
                                <a:dropdown_alerts>
                            </div>     
                            <div class="panel-footer text-center">
                                <a href="/admin/alerts">Show all Alerts</a>
                            </div>                            
                        </div>                        
                    </li>
                    <!-- END TASKS -->
                    <!-- LANG BAR
                    <li class="xn-icon-button pull-right">
                        <a href="#"><span class="flag flag-gb"></span></a>
                        <ul class="xn-drop-left xn-drop-white animated zoomIn">
                            <li><a href="#"><span class="flag flag-gb"></span> English</a></li>
                            <li><a href="#"><span class="flag flag-de"></span> Deutsch</a></li>
                            <li><a href="#"><span class="flag flag-cn"></span> Chinese</a></li>
                        </ul>                        
                    </li> 
                    END LANG BAR -->
                  
                </ul>
                <!-- END X-NAVIGATION VERTICAL -->                     

                <!-- START BREADCRUMB 
                <ul class="breadcrumb">
                    <li><a href="#">Home</a></li>                    
                    <li class="active">Dashboard</li>
                </ul>
                 END BREADCRUMB -->                       
              

                
                <div class="page-title">                    
                    <h2><span class="fa fa-arrow-circle-o-left"></span> <a:page_title textonly="1"></h2>
                </div>                   


                
                <!-- PAGE CONTENT WRAPPER -->
                <div class="page-content-wrap">

