This modification provides [SMF][link1] the ability to publish calendar entries to a Google calendar. In addition, there is underlying logic to enable SMF to modify the Google calendar entry when its modified within SMF.

The need for this came when members of the forum that I run asked for iCal integration. Since Google Calendar had support for iCal, and a whole host of other platforms, it made the most sense to publish calendar entries to a Google calendar, which the users could sync to.

Since this modification uses the Google Calendar API, the [Zend
Framework][link2] is required for this modification to work. Currently, I've
successfully tested with version 1.10.6, and so your mileage may vary with
newer versions. Since the Zend Framework *requires* PHP 5, this modification
will *not* work if your server is running PHP 4.

Once installed, the modification only requires the e-mail and password associated with the Google Calendar that you'd like to publish entries to.

This is definitely a work in progress, and there is most certainly no warranty supplied with this code at all. In addition, I'm continuing to develop this through additional features and fixes. If you have any suggestions or would like to contribute code, please contact me via e-mail.

[link1]: http://simplemachines.org/ "SimpleMachines Forum"
[link2]: http://framework.zend.com/ "Zend Framework"
