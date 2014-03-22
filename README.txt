bot-blocker
===========

A spam bot blocker for web sites.

We will use two methods for capturing bots that come to the site.

1. Using a trap that captures info about the IP address of the bot that does not follow the robot.txt file. The sample robot.txt file included can safely be used with a Drupal installation.

2. Check the visitors IP address against DNSBL sites. If the vote is more then 50% (can be easily changed) between them the IP address is a bot and will be banned.


TO DO'S
1. Split information results into seprate files
2. Add option if you would like to share your list of IP addresses with the world.
3. Add DB functionality (not recommended for high traffic sites)
4. Add un-ban functionality. Through a captcha form perhaps?

How To;
1. You will need to do is include "dir/blackhole.php"; into every file you want to run the bot trap.
2. Add hidden links into your exposed HTML for the evil bot to follow. It is advisable to add rel="nofollow" as well. This will keep legal bots from being banned as well.
