<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title><a:page_title textonly="1"></title>

	<!-- Global stylesheets -->
	<link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">
	<link href="~theme_uri~/css/icons/icomoon/styles.css" rel="stylesheet" type="text/css">
	<link href="~theme_uri~/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
	<link href="~theme_uri~/assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
	<link href="~theme_uri~/assets/css/layout.min.css" rel="stylesheet" type="text/css">
	<link href="~theme_uri~/assets/css/components.min.css" rel="stylesheet" type="text/css">
	<link href="~theme_uri~/assets/css/colors.min.css" rel="stylesheet" type="text/css">
    <link href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
	<!-- /global stylesheets -->

	<!-- new modifications CSS -->
	<link href="~theme_uri~/assets/css/newcss.css?v=1.60" rel="stylesheet" type="text/css">
	<!-- /new modifications CSS -->


	<!-- Core JS files -->
	<script src="~theme_uri~/js/main/jquery.min.js"></script>
    <script src="~theme_uri~/js/main/jquery-ui.min.js"></script>
	<script src="~theme_uri~/js/main/bootstrap.bundle.min.js"></script>
	<script src="~theme_uri~/js/plugins/loaders/blockui.min.js"></script>
	<!-- /core JS files -->

	<!-- Theme JS files -->
	<script src="~theme_uri~/js/plugins/visualization/d3/d3.min.js"></script>
	<script src="~theme_uri~/js/plugins/visualization/d3/d3_tooltip.js"></script>
	<script src="~theme_uri~/js/plugins/forms/styling/switchery.min.js"></script>
	<script src="~theme_uri~/js/plugins/forms/selects/bootstrap_multiselect.js"></script>
	<script src="~theme_uri~/js/plugins/ui/moment/moment.min.js"></script>
	<script src="~theme_uri~/js/plugins/pickers/daterangepicker.js"></script>

	<script src="~theme_uri~/assets/js/app.js"></script>
	<script src="~theme_uri~/js/demo_pages/dashboard.js"></script>
	<!-- /theme JS files -->

</head>

<body>

	<!-- Main navbar -->
	<div class="navbar navbar-expand-md navbar-dark">
		<div class="navbar-brand">
			<a href="~site_uri~/admin/index" class="d-inline-block">
				<img src="~theme_uri~/images/logo_light.png" alt="">
			</a>
		</div>

		<div class="d-md-none">
			<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar-mobile">
				<i class="icon-tree5"></i>
			</button>
			<button class="navbar-toggler sidebar-mobile-main-toggle" type="button">
				<i class="icon-paragraph-justify3"></i>
			</button>
		</div>

		<div class="collapse navbar-collapse" id="navbar-mobile">
			<ul class="navbar-nav">
				<li class="nav-item">
					<a href="#" class="navbar-nav-link sidebar-control sidebar-main-toggle d-none d-md-block">
						<i class="icon-paragraph-justify3"></i>
					</a>
				</li>


				<li class="nav-item dropdown">
					<a href="#" onclick="ajax_send('core/clear_dropdown', 'dropdown=alerts&auth_type=admin');" class="navbar-nav-link dropdown-toggle caret-0" data-toggle="dropdown">
						<i class="icon-git-compare"></i>
						<span class="d-md-none ml-2">Notifications</span>
						<span id="badge_unread_alerts" style="display: ~display_unread_alerts~" class="badge badge-pill bg-warning-400 ml-auto ml-md-0">~unread_alerts~</span>
					</a>

					<div class="dropdown-menu dropdown-content wmin-md-350">
						<div class="dropdown-content-header">
							<span class="font-weight-semibold">Notifications</span>
							<a href="#" class="text-default"><i class="icon-sync"></i></a>
						</div>

						<div class="dropdown-content-body dropdown-scrollable">
							<ul class="media-list" id="dropdown_alerts">
                                <a:dropdown_alerts>
							</ul>
						</div>

						<div class="dropdown-content-footer bg-light">
							<a href="/admin/alerts" class="text-grey mr-auto">All Notifications</a>
							<div>
								<a href="#" onclick="ajax_send('core/clear_dropdown', 'dropdown=alerts&auth_type=admin&clearall=1');" class="text-grey" data-popup="tooltip" title="Mark all as read"><i class="icon-radio-unchecked"></i></a>
							
							</div>
						</div>
					</div>
				</li>
			</ul>

			<span class="navbar-text ml-md-3 mr-md-auto">
				<span class="badge bg-success">Online</span>
			</span>


			<ul class="navbar-nav">

    <!-- 
				<li class="nav-item dropdown">
					<a href="#" class="navbar-nav-link dropdown-toggle caret-0" data-toggle="dropdown">
						<i class="icon-people"></i>
						<span class="d-md-none ml-2">Messages</span>
					</a>
					
					<div class="dropdown-menu dropdown-menu-right dropdown-content wmin-md-300">
						<div class="dropdown-content-header">
							<span class="font-weight-semibold">Messages</span>
							<a href="#" class="text-default"><i class="icon-search4 font-size-base"></i></a>
						</div>

						<div class="dropdown-content-body dropdown-scrollable">
							<ul class="media-list">
								<li class="media">
									<div class="mr-3">
										<img src="~theme_uri~/images/placeholders/placeholder.jpg" width="36" height="36" class="rounded-circle" alt="">
									</div>
									<div class="media-body">
										<a href="#" class="media-title font-weight-semibold">Jordana Ansley</a>
										<span class="d-block text-muted font-size-sm">Lead web developer</span>
									</div>
									<div class="ml-3 align-self-center"><span class="badge badge-mark border-success"></span></div>
								</li>

								<li class="media">
									<div class="mr-3">
										<img src="~theme_uri~/images/placeholders/placeholder.jpg" width="36" height="36" class="rounded-circle" alt="">
									</div>
									<div class="media-body">
										<a href="#" class="media-title font-weight-semibold">Will Brason</a>
										<span class="d-block text-muted font-size-sm">Marketing manager</span>
									</div>
									<div class="ml-3 align-self-center"><span class="badge badge-mark border-danger"></span></div>
								</li>

								<li class="media">
									<div class="mr-3">
										<img src="~theme_uri~/images/placeholders/placeholder.jpg" width="36" height="36" class="rounded-circle" alt="">
									</div>
									<div class="media-body">
										<a href="#" class="media-title font-weight-semibold">Hanna Walden</a>
										<span class="d-block text-muted font-size-sm">Project manager</span>
									</div>
									<div class="ml-3 align-self-center"><span class="badge badge-mark border-success"></span></div>
								</li>

								<li class="media">
									<div class="mr-3">
										<img src="~theme_uri~/images/placeholders/placeholder.jpg" width="36" height="36" class="rounded-circle" alt="">
									</div>
									<div class="media-body">
										<a href="#" class="media-title font-weight-semibold">Dori Laperriere</a>
										<span class="d-block text-muted font-size-sm">Business developer</span>
									</div>
									<div class="ml-3 align-self-center"><span class="badge badge-mark border-warning-300"></span></div>
								</li>

								<li class="media">
									<div class="mr-3">
										<img src="~theme_uri~/images/placeholders/placeholder.jpg" width="36" height="36" class="rounded-circle" alt="">
									</div>
									<div class="media-body">
										<a href="#" class="media-title font-weight-semibold">Vanessa Aurelius</a>
										<span class="d-block text-muted font-size-sm">UX expert</span>
									</div>
									<div class="ml-3 align-self-center"><span class="badge badge-mark border-grey-400"></span></div>
								</li>
							</ul>
						</div>

						<div class="dropdown-content-footer bg-light">
							<a href="#" class="text-grey mr-auto">All users</a>
							<a href="#" class="text-grey"><i class="icon-gear"></i></a>
						</div>
					</div>
				</li>
    -->

				<li class="nav-item dropdown">
					<a href="#" onclick="ajax_send('core/clear_dropdown', 'dropdown=messages&auth_type=admin');" class="navbar-nav-link dropdown-toggle caret-0" data-toggle="dropdown">
						<i class="icon-bubbles4"></i>
						<span class="d-md-none ml-2">Messages</span>
						<span id="badge_unread_messages" class="badge badge-pill bg-warning-400 ml-auto ml-md-0" style="display: ~display_badge_unread_messages~;">~unread_messages~</span>
					</a>
					
					<div class="dropdown-menu dropdown-menu-right dropdown-content wmin-md-350">
						<div class="dropdown-content-header">
							<span class="font-weight-semibold">Messages</span>
							<a href="/admin/support/create_ticket" class="text-default"><i class="icon-compose"></i></a>
						</div>

						<div class="dropdown-content-body dropdown-scrollable">
							<ul class="media-list" id="dropdown_messages">
                                <a:dropdown_messages>
							</ul>
						</div>

						<div class="dropdown-content-footer justify-content-center p-0">
							<a href="#" class="bg-light text-grey w-100 py-2" data-popup="tooltip" title="Load more"><i class="icon-menu7 d-block top-0"></i></a>
						</div>
					</div>
				</li>

				<li class="nav-item dropdown dropdown-user">
					<a href="#" class="navbar-nav-link dropdown-toggle" data-toggle="dropdown">
						<img src="~theme_uri~/images/placeholders/placeholder.jpg" class="rounded-circle" alt="">
						<span>~profile.full_name~</span>
					</a>

					<div class="dropdown-menu dropdown-menu-right">
						<a href="/admin/settings/admin_manage?admin_id=~profile.id~" class="dropdown-item"><i class="icon-user-plus"></i> My profile</a>
						<a href="/admin/support/inbox" class="dropdown-item"><i class="icon-comment-discussion"></i> Messages <span class="badge badge-pill bg-blue ml-auto">~unread_messages~</span></a>
						<div class="dropdown-divider"></div>
						<a href="/admin/logout" class="dropdown-item"><i class="icon-switch2"></i> Logout</a>
					</div>
				</li>
			</ul>
		</div>
	</div>
	<!-- /main navbar -->


	<!-- Page content -->
	<div class="page-content">

		<!-- Main sidebar -->
		<div class="sidebar sidebar-dark sidebar-main sidebar-expand-md">

			<!-- Sidebar mobile toggler -->
			<div class="sidebar-mobile-toggler text-center">
				<a href="#" class="sidebar-mobile-main-toggle">
					<i class="icon-arrow-left8"></i>
				</a>
				Navigation
				<a href="#" class="sidebar-mobile-expand">
					<i class="icon-screen-full"></i>
					<i class="icon-screen-normal"></i>
				</a>
			</div>
			<!-- /sidebar mobile toggler -->


			<!-- Sidebar content -->
			<div class="sidebar-content">

				<!-- User menu -->
				<div class="sidebar-user">
					<div class="card-body">
						<div class="media">
							<div class="mr-3">
								<a href="#"><img src="~theme_uri~/images/placeholders/placeholder.jpg" width="38" height="38" class="rounded-circle" alt=""></a>
							</div>

							<div class="media-body">
								<div class="media-title font-weight-semibold">Victoria Baker</div>
								<div class="font-size-xs opacity-50">
									<i class="icon-pin font-size-sm"></i> &nbsp;Santa Ana, CA
								</div>
							</div>

							<div class="ml-3 align-self-center">
								<a href="#" class="text-white"><i class="icon-cog3"></i></a>
							</div>
						</div>
					</div>
				</div>
				<!-- /user menu -->


				<!-- Main navigation -->
				<div class="card card-sidebar-mobile">
					<ul class="nav nav-sidebar" data-nav-type="accordion">
                        <a:if ~userid~ != 0>
                            <a:nav_menu>
                        </a:if>
					</ul>
				</div>
				<!-- /main navigation -->

			</div>
			<!-- /sidebar content -->
			
		</div>
		<!-- /main sidebar -->


		<!-- Main content -->
		<div class="content-wrapper">

			<!-- Page header -->
			<div class="page-header page-header-light">
				<div class="page-header-content header-elements-md-inline">
					<div class="page-title d-flex">
						<h4><i class="icon-arrow-left52 mr-2"></i> <a:page_title textonly="1"></h4>
					</div>
                </div>
            </div>

            <div class="content">
                <div class="">

                    <a:callouts>



