
# Selling your Software, and Setting up a Repo

This guide serves to provide step-by-step directions on how to setup your own repository, publish your commercial code to 
it, and provide your paying customers with access for installation and upgrades.  

### Setup and Publish Package

Setup your own repository, develop your package, and publish it with the following steps:

1. Do a [base installation](../install) of Apex, which should only take five minutes.  
2. Install some base packages, including the "devkit" package which will turn your Apex installation into its own repository with: `./apex install webapp users transaction support devkit`
3. Create your package with `./apex create_package mypackage`.  It will prompt you for which repository you would like to publish to, and select 2 for your own repo.
4. Inside the newly created /etc/mypackage/package.php file, change the `$access` property to "private".  This will ensure only paying customers can download / install your package.
5. Develop your package as desired, and please refer to the full documentation on how to do so.
6. Login to the admin panel, and create yourself a user account via the Users-&gt;Create New User menu.  Within terminal, run `./apex update_repo myhostname.com` and enter the user / pass of the user account you created.
7. Publish your package to your repository with: `./apex publish mypackage`.
8. Create an upgrade point with `./apex create_upgrade mypackage`

Cool, so we now have our repository setup, and our private package published to it.  We also have created an upgrade point, 
so you can continue developing and release an upgrade any time with `./apex publish_upgrade mypackage`.  Apex will automatically detect all changes 
made to your package since you defined the upgrade point, will package them, and upload them to your repository making them immediately available to all customer installs.


### Setting up Customers

At the time of writing, although there is a full transaction and payment system in Apex, there is currently no integration with payment processors such as PayPal or Stripe, 
nor any order / purchase functionality.  You're highly encouraged to jump in, develop it and contribute to Apex, and otherwise you will just have to be patient and wait 
for me to get around to it.  Considering that, for the time being you will need to manually create your customer accounts.

When a new customer purchases, follow these steps:

1. Login to your admin panel, and create a new user account via the Users-&gt;Create New User menu.
2. Manage the user via the Users-&gt;Manage User menu.  You will see a Packages tab where you can give the user access to your private package.
3. Do a base installation of Apex on the customer's server.
4. Add your repository into Apex with: `./apex add_repo myhostname.com USER PASS`
5. Install your private package with: `./apex install mypackage`

Apex will find the package within your repository, authenticate the login credentials since it's a private package, then instantly download and install the 
package on the customer's server.  That's it, your customer is now all setup!


### Publishing Upgrades

We already created an upgrade point on our package, so go ahead and make any desired modifications / enhancements to the package you would like.  When ready (and all unit tests check out of course), 
you can go ahead and publish the upgrade to your repository with `./apex publish_upgrade mypackage`.  Apex will automatically detect all changes to the package, and upload them to your 
repository.

The upgrade can then be instantly installed on any customer's server with `./apex upgrade`.  Assuming their login credentials are still correct, it will 
download and install the upgrade from your repository.


### Conclusion

Hope this guide serves your needs well.  Any questions, commends, feedback, suggestions, of if you'd like to contribute, 
please drop us a line at hello@apex-platform.org, or drop a message on Reddit at [/r/Apex_Platform](https://reddit.com/r/Apex_Platform)



