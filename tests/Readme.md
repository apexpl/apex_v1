
# Unit Tests

This directory will contain one sub-directory for every package, each of which contains that package's unit tests.  For full 
information on how to develop and manage unit tests within Apex, please visit the documentation at:
    https://apexpl.io/docs/tests

## Core Tests

For size and cleanliness reasons, all core Apex platform tests are stored within a separate package.  You may install them within terminal by typing:
    ./apex install core_tests

In order to execute the tests, you must ensure the following:

1. The following base packages are installed:  *webapp, maintenance, multi_admins, cms, cms_pages users, transaction, support, devkit*
2. The RPC and WebSocket servers must be running, which can be turned on via terminal by typing:  *./apex core.rpc &* and *./apex core.websocket &*
3. The admin and user account username / password combinations found within the phpunit.xml file must be created within the system.  If desired, you can automatically create these by installing the *demo* package with: *./apex install demo*




