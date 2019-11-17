
# Apex - Upgrades

Now that are package is developed and published, we will want to maintain it by releasing occassional upgrades.  To do this, we need to 
first create an upgrade point, so in terminal type:

`./apex create_upgrade training`

This will hash all files and components within the "traning" package.  You can then go ahead and may any desired changes 
to the package, and it will be automatically tracked upon publishing the upgrade.  You will also notice a new directory at */etc/training/upgrades/1.0.1* that contains a couple SQL files, plus a package.php file 
that is executed upon installation of the upgrade.



### Publish Upgrade

Once you've completed the desired modifications to your package, you can publish the upgrade to the repository at anytime within terminal with:

`./apex publish_upgrade training`

This will compre the hash created upon creating the upgrade point with the files and components currently in the package, compile the 
upgrade as necessary, and upload it to the repository.  From there, the upgrade can be instantly installed on any system with the package installed with:

`./apex upgrade`



### Conclusion

That's it for this training guide, and it should give you a quick introduction into Apex.  There's quite a bit more to Apex such as horizontal scaling, 
redis, web sockets, and more that we never covered in this training guide.  However, there will be additional training guides coming online shortly.

Thanks for your time in going through this introduction training guide to Apex.  If you have any questions, comments, or suggestions, please don't hesitate to contact 
us directly, or drop a message on the [Reddit Forum](https://reddit.com/r/Apex_Platform).


