# moodle-local_course_trash

Current version: 1.0

(Plans for v2.0: personal sourse trash with ability to restore & autodelete on timeout, e.g. 30 days, via scheduled task. Also logging the deletion events into DB.)


INSTALLATION USING GIT

1) Execute "git clone https://github.com/den1s0v/moodle-local_course_trash.git".

2) Copy `course_trash` directory to `<Moodle root>/local/` directory.

3) Visit `/admin/index.php` as site administrator and follow plugin installation instructions.

4) That's it. 


USAGE

1) Now any teacher can sent his/her own courses to trash in course admin menu.

2) To restore a trashed course, administrator (or user with 'view any course' capability) should use "Restore course" entry in course admin menu. (The same URL is stored within the summary of trashed course; see course settings).

3) To delete a course completely, use Moodle's standard course removal features.


THANKS

This plugin is based on [local_delete_course by Marcelo A. Rauh Schmitt](https://github.com/marceloschmitt/moodle-local_delete_course).


Version log

Version 1.0: Simply hide the course & move course to a dedicated category.

