Advanced bulk user actions
=============================

Its code was updated so that now it has a plug-in structure, enabling developers to add new bulk actions functionality more easily, on the base of single code. Shortly, all actions are stored in their own subfolders in /admin/user/actions/ folder, and the base code redirects user to index.php in the action’s folder. To simplify the implementation even further, necessary language strings can be stored in corresponding subfolder of /admin/user/lang/ folder and fetched by get_string function with action-specific parameters. All “standard” actions (view, confirm, delete…) were edited to fit this plug-in structure and perform the same.

Additionally, some actions were added:

1. Assign role in courses
1. Continue manual enrolments of users
1. Enrol to courses
1. Suspend manual enrolments of users
1. Unassing roles in courses
1. Unenrol from courses

Requirements
=============================

Moodle 2.0, 2,1

For Moodle 2.3 see https://github.com/andreev-artem/moodle_admin_tool_advuserbulk

Installing
=============================

Extract the contents of andreev-artem-moodle_admin_tool_advuserbulk-xxxxxxx folder inside the archive you've downloaded to folder admin/tool/advuserbulk.

After installation you can find link "Advanced bulk user actions" in Administration > Users > Accounts