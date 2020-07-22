//
function ciniki_projects_main() {
    //
    // Panels
    //
    this.dayschedule = null;

    this.cb = null;
    this.toggleOptions = {'off':'Off', 'on':'On'};
//  this.durationOptions = {'1440':'All day', '15':'15', '30':'30', '45':'45', '60':'60', '90':'1:30', '120':'2h'};
//  this.durationButtons = {'-30':'-30', '-15':'-15', '+15':'+15', '+30':'+30', '+2h':'+120'};
//  this.repeatOptions = {'10':'Daily', '20':'Weekly', '30':'Monthly by Date', '31':'Monthly by Weekday','40':'Yearly'};
//  this.repeatIntervals = {'1':'1', '2':'2', '3':'3', '4':'4', '5':'5', '6':'6', '7':'7', '8':'8'};
    this.statuses = {'1':'Open', '60':'Completed'};
    this.symbolpriorities = {'10':'Q', '30':'W', '50':'E'}; // also stored in core_menu.js
    this.permFlags = {'1':{'name':'Private'}};
    this.statusOptions = {'10':'Open', '30':'Future', '40':'Dormant', '50':'Completed', '60':'Deleted'};

    this.init = function() {

        //
        // The default panel will show the projects in a list based on assignment
        //
        this.projects = new M.panel('Projects',
            'ciniki_projects_main', 'projects',
            'mc', 'medium', 'sectioned', 'ciniki.projects.main.projects');
        this.projects.sections = {};
        this.projects.data = {};
//      this.projects.noData = function() { return 'No projects found'; }
        // Live Search functions
        this.projects.liveSearchCb = function(s, i, v) {
            M.api.getJSONBgCb('ciniki.projects.searchNames', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'15'},
                function(rsp) {
                    M.ciniki_projects_main.projects.liveSearchShow(s, null, M.gE(M.ciniki_projects_main.projects.panelUID + '_' + s), rsp.projects);
                });
            return true;
        };
        this.projects.liveSearchResultClass = function(s, f, i, j, d) {
            return this.sections[s].cellClasses[j];
        };
        this.projects.liveSearchResultValue = function(s, f, i, j, d) {
            if( j == 0 ) {
                if( d.viewed == 'no' ) {
                    return '<b>' + d.name + '</b>';
                }
                return d.name;
            }
            if( j == 1 ) { return d.pstatus_text; }
            return '';
        };
        this.projects.liveSearchResultRowFn = function(s, f, i, j, d) {
            return 'M.ciniki_projects_main.showProject(\'M.ciniki_projects_main.showProjects(null, null);\', \'' + d.id + '\');'; 
        };
//      this.projects.liveSearchSubmitFn = function(s, search_str) {
//          M.ciniki_atdo_main.searchProjects('M.ciniki_atdo_main.showProjects();', search_str);
//      };
        this.projects.cellValue = function(s, i, j, d) {
            if( j == 0 ) {
                if( d.project.viewed == 'no' ) {
                    return '<b>' + d.project.name + '</b>';
                }
                return d.project.name;
            }
            if( j == 1 ) { return d.project.status_text; }
        };
        this.projects.rowFn = function(s, i, d) {
            return 'M.ciniki_projects_main.showProject(\'M.ciniki_projects_main.showProjects();\', \'' + d.project.id + '\');'; 
        };
        this.projects.sectionData = function(s) { 
            return this.data[s];
        };
//      this.projects.listValue = function(s, i, d) { 
//          if( d.count != null ) {
//              return d.label + ' <span class="count">' + d.count + '</span>'; 
//          }
//          return d.label;
//      };

        this.projects.addButton('add', 'Add', 'M.ciniki_projects_main.showEdit(\'M.ciniki_projects_main.showProjects();\',0);');
        this.projects.addClose('Back');

        //
        // Then to display an bottling appointment
        //
        this.edit = new M.panel('Edit',
            'ciniki_projects_main', 'edit',
            'mc', 'medium', 'sectioned', 'ciniki.projects.main.edit');
        this.edit.project_id = 0;
        this.edit.data = null;
        this.edit.default_data = {'status':'10'};
        this.edit.sections = {
            'info':{'label':'', 'type':'simpleform', 'fields':{
                'category':{'label':'Project Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
                'name':{'label':'Project Name', 'type':'text'},
                'assigned':{'label':'Assigned', 'type':'multiselect', 'none':'yes', 'options':M.curTenant.employees},
                'perm_flags':{'label':'Options', 'type':'flags', 'none':'yes', 'flags':this.permFlags},
                'status':{'label':'Status', 'type':'toggle', 'none':'no', 'toggles':this.statusOptions},
            }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_projects_main.saveProject();'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_projects_main.deleteProject();'},
                }},
            };
        this.edit.sectionData = function(s) {
            return this.data[s];
        };
        this.edit.listFn = function(s, i, d) { return d.fn; };
        this.edit.fieldValue = function(s, i, d) { 
            return this.data[i];
        };
        this.edit.liveSearchCb = function(s, i, value) {
            if( i == 'category' ) {
                var rsp = M.api.getJSONBgCb('ciniki.projects.searchCategory', {'tnid':M.curTenantID, 'start_needle':value, 'limit':15},
                    function(rsp) {
                        M.ciniki_projects_main.edit.liveSearchShow(s, i, M.gE(M.ciniki_projects_main.edit.panelUID + '_' + i), rsp.categories);
                    });
            }
        };
        this.edit.liveSearchResultValue = function(s, f, i, j, d) {
            if( f == 'category' && d.category != null ) { return d.category.name; }
            return '';
        };
        this.edit.liveSearchResultRowFn = function(s, f, i, j, d) { 
            if( f == 'category' && d.category != null ) {
                return 'M.ciniki_projects_main.edit.updateCategory(\'' + s + '\',\'' + escape(d.category.name) + '\');';
            }
        };
        this.edit.updateCategory = function(s, category) {
            M.gE(this.panelUID + '_category').value = unescape(category);
            this.removeLiveSearch(s, 'category');
        };
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.projects.getHistory', 'args':{'tnid':M.curTenantID, 
                'project_id':this.project_id, 'field':i}};
        }
        this.edit.addButton('save', 'Save', 'M.ciniki_projects_main.saveProject();');
        this.edit.addClose('Cancel');

        //
        // The project panel for displaying the overview of a project
        //
        this.project = new M.panel('Project',
            'ciniki_projects_main', 'project',
            'mc', 'medium', 'sectioned', 'ciniki.projects.main.project');
        this.project.data = {};
        this.project.project_id = 0;
        this.project.sections = {
            'info':{'label':'', 'list':{
                'category':{'label':'Category'},
                'name':{'label':'Name'},
                'assigned':{'label':'Assigned'},
                'status_text':{'label':'Status'},
                }},
            'appointments':{'label':'Appointments', 'type':'simplegrid', 'num_cols':'1', 
                'headerValues':null,
                'cellClasses':['multiline','multiline'],
                'addTxt':'Add Appointment',
                'addFn':'M.startApp(\'ciniki.atdo.main\',null,\'M.ciniki_projects_main.showProject();\',\'mc\',{\'addtoproject\':\'appointment\',\'project_id\':M.ciniki_projects_main.project.project_id,\'project_name\':escape(M.ciniki_projects_main.project.data.name)});',
                },
            'tasks':{'label':'Tasks', 'type':'simplegrid', 'num_cols':'3',
                'headerValues':['', 'Task', 'Due'],
                'cellClasses':['multiline aligncenter','multiline','multiline'],
                'addTxt':'Add Task',
                'addFn':'M.startApp(\'ciniki.atdo.main\',null,\'M.ciniki_projects_main.showProject();\',\'mc\',{\'addtoproject\':\'task\',\'project_id\':M.ciniki_projects_main.project.project_id,\'project_name\':escape(M.ciniki_projects_main.project.data.name)});',
                },
            'messages':{'label':'Messages', 'type':'simplegrid', 'num_cols':'1',
                'headerValues':null,
                'cellClasses':['multiline'],
                'addTxt':'Add Message',
                'addFn':'M.startApp(\'ciniki.atdo.main\',null,\'M.ciniki_projects_main.showProject();\',\'mc\',{\'addtoproject\':\'message\',\'project_id\':M.ciniki_projects_main.project.project_id,\'project_name\':escape(M.ciniki_projects_main.project.data.name)});',
                },
            'notes':{'label':'Notes', 'type':'simplegrid', 'num_cols':'1',
                'headerValues':null,
                'cellClasses':[''],
                'addTxt':'Add Note',
                'addFn':'M.startApp(\'ciniki.atdo.main\',null,\'M.ciniki_projects_main.showProject();\',\'mc\',{\'addtoproject\':\'note\',\'project_id\':M.ciniki_projects_main.project.project_id,\'project_name\':escape(M.ciniki_projects_main.project.data.name)});',
                },
            'files':{'label':'Files', 'visible':'no', 'type':'simplegrid', 'num_cols':'2',
                'headerValues':null,
                'cellClasses':['multiline','multiline'],
                'addTxt':'Add File',
                'addFn':'M.startApp(\'ciniki.filedepot.main\',null,\'M.ciniki_projects_main.showProject();\',\'mc\',{\'add\':\'project\',\'project_id\':M.ciniki_projects_main.project.project_id,\'project_name\':escape(M.ciniki_projects_main.project.data.name)});',
                },
        };
        this.project.listLabel = function(s, i, d) { return d.label; }
        this.project.listValue = function(s, i, d) { 
            if( s == 'info' ) {
                if( i == 'assigned' ) {
                    var str = '';
                    var users = this.data.assigned.split(/,/);
                    for(i in users) {
                        if( M.curTenant.employees[users[i]] != null ) {
                            if( str == '' ) {
                                str = M.curTenant.employees[users[i]];
                            } else {
                                str += ', ' + M.curTenant.employees[users[i]];
                            }
                        }
                    }
                    return str;
                }
                if( i == 'status_text' ) { return M.ciniki_projects_main.statusOptions[this.data['status']]; }
                return this.data[i];
            }
        };
        this.project.cellValue = function(s, i, j, d) {
            // Appointments and calendars both return the same format
            if( s == 'appointments' ) {
                if( j == 0 ) { 
                    var t = '';
                    t += '<span class="maintext">' + d.appointment.subject + '</span>';
                    t += '<span class="subtext">';
                    if( d.appointment.start_ts == 0 ) { 
                        t += 'unscheduled';
                    }   
                    else if( d.appointment.allday == 'yes' ) { 
                        t += d.appointment.start_date.split(/ [0-9]+:/)[0];
                    } else {
                        t += d.appointment.start_date.split(/ [0-9]+:/)[0] + ' - ' + d.appointment.start_date.split(/, [0-9][0-9][0-9][0-9] /)[1] + '';
                    }
                    t += '</span>';
                    return t;
                }
            }
            if( s == 'tasks' ) {
                if( j == 0 ) { return '<span class="icon">' + M.ciniki_projects_main.symbolpriorities[d.task.priority] + '</span>'; }
                if( j == 1 ) {
                    return '<span class="maintext">' + d.task.subject + '</span><span class="subtext">' + d.task.assigned_users + '&nbsp;</span>';
                }
                if( j == 2 ) { return '<span class="maintext">' + d.task.due_date + '</span><span class="subtext">' + d.task.due_time + '</span>'; }
            }
            if( s == 'notes' ) {
                if( j == 0 ) {
                    if( d.note.viewed == 'no' ) {
                        return '<b>' + d.note.subject + '</b>';
                    }
                    return d.note.subject;
                }
            }
            if( s == 'messages' ) {
                if( j == 0 ) { 
                    var last = '<span class="subtext">' + d.message.last_followup_user + ' - ' + d.message.last_followup_age + ' ago</span>';
                    if( d.message.viewed == 'no' ) {
                        return '<span class="maintext"><b>' + d.message.subject + '</b>' + '</span>' + last;
                    }
                    return '<span class="maintext">' + d.message.subject + '</span>' + last; 
                }
            }
            if( s == 'files' ) {
                if( j == 0 ) { 
                    return '<span class="maintext">' + d.file.name + ' ' + d.file.version + '</span><span class="subtext">' + d.file.org_filename + '</span>';
                }
                if( j == 1 ) {
                    return '<span class="maintext">' + d.file.date_added + '</span><span class="subtext">' + d.file.shared + '</span>';
                }
            }
        };
        this.project.rowStyle = function(s, i, d) {
            if( s == 'tasks' ) {
                if( d != null && d.task != null ) {
                    if( d.task.status != 'closed' ) { return 'background: ' + M.curTenant.atdo.settings['tasks.priority.' + d.task.priority]; }
                    else { return 'background: ' + M.curTenant.atdo.settings['tasks.status.60']; }
                }
            }
            return '';
        };
        this.project.cellFn = function(s, i, j, d) {
//          if( s == 'appointments' ) { return 'M.ciniki_projects_main.showAtdo(\'M.ciniki_projects_main.showProject();\', \'' + d.appointment.id + '\');'; };
            if( s == 'appointments') { return 'M.startApp(\'ciniki.atdo.main\',null,\'M.ciniki_projects_main.showProject();\',\'mc\',{\'atdo_id\':\'' + d.appointment.id + '\'});'; };
            return null;
        };
        this.project.rowFn = function(s, i, d) {
            switch (s) {
                case 'appointments': return 'M.startApp(\'ciniki.atdo.main\',null,\'M.ciniki_projects_main.showProject();\',\'mc\',{\'atdo_id\':\'' + d.appointment.id + '\'});';
                case 'tasks': return 'M.startApp(\'ciniki.atdo.main\',null,\'M.ciniki_projects_main.showProject();\',\'mc\',{\'atdo_id\':\'' + d.task.id + '\'});';
                case 'notes': return 'M.startApp(\'ciniki.atdo.main\',null,\'M.ciniki_projects_main.showProject();\',\'mc\',{\'atdo_id\':\'' + d.note.id + '\'});';
                case 'messages': return 'M.startApp(\'ciniki.atdo.main\',null,\'M.ciniki_projects_main.showProject();\',\'mc\',{\'atdo_id\':\'' + d.message.id + '\'});';
                case 'files': return 'M.startApp(\'ciniki.filedepot.main\',null,\'M.ciniki_projects_main.showProject();\',\'mc\',{\'file_id\':\'' + d.file.id + '\'});';
//              case 'appointments': return 'M.ciniki_atdo_main.showAtdo(\'M.ciniki_projects_main.showProject();\', \'' + d.appointment.id + '\');';
//              case 'tasks': return 'M.ciniki_atdo_main.showAtdo(\'M.ciniki_projects_main.showProject();\', \'' + d.task.id + '\');'; 
//              case 'notes': return 'M.ciniki_atdo_main.showAtdo(\'M.ciniki_projects_main.showProject();\', \'' + d.note.id + '\');'; 
//              case 'messages': return 'M.ciniki_atdo_main.showAtdo(\'M.ciniki_projects_main.showProject();\', \'' + d.message.id + '\');'; 
            }
        };
        this.project.sectionData = function(s) { 
            if( s == 'info' ) { return this.sections.info.list; }
            return this.data[s];
        };
        this.project.addButton('edit', 'Edit', 'M.ciniki_projects_main.showEdit(\'M.ciniki_projects_main.showProject();\',M.ciniki_projects_main.project.project_id);');
        this.project.addClose('Back');
    }

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        //
        // Reset all employee lists, must be done when switching tenants
        //
        this.edit.sections.info.fields.assigned.options = M.curTenant.employees;

        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_projects_main', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        if( M.curTenant.modules['ciniki.filedepot'] != null ) {
            M.ciniki_projects_main.project.sections.files.visible = 'yes';
        } else {
            M.ciniki_projects_main.project.sections.files.visible = 'no';
        }

        this.cb = cb;
        // this.files.show(cb);
        if( args.project_id != null && args.project_id != '' ) {
            this.showProject(cb, args.project_id);
        } else {
            this.showProjects(cb, 10);
        }
    }

    this.showProjects = function(cb, status) {
        // Get the projects for the user and tenant
        this.projects.data = {};
        if( status != null ) { this.projects.status = status; }
        var p = M.ciniki_projects_main.projects;
        p.sections = {
            'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':2, 'hint':'search', 
                'noData':'No projects found',
                'headerValues':null,
                'cellClasses':[''],
                },
            'status_select':{'label':'', 'visible':'yes', 'type':'paneltabs', 'selected':this.projects.status, 'tabs':{
                '0':{'label':'All', 'fn':'M.ciniki_projects_main.showProjects(null,0);'},
                '10':{'label':'Open', 'fn':'M.ciniki_projects_main.showProjects(null,10);'},
                '30':{'label':'Future', 'fn':'M.ciniki_projects_main.showProjects(null,30);'},
                '40':{'label':'Dormant', 'fn':'M.ciniki_projects_main.showProjects(null,40);'},
                '50':{'label':'Completed', 'fn':'M.ciniki_projects_main.showProjects(null,50);'},
                '60':{'label':'Deleted', 'fn':'M.ciniki_projects_main.showProjects(null,60);'},
                }},
            };
        var rsp = M.api.getJSONCb('ciniki.projects.projectList', 
            {'tnid':M.curTenantID, 'status':this.projects.status}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                for(i in rsp.categories) {
                    p.data[rsp.categories[i].category.name] = rsp.categories[i].category.projects;
                    p.sections[rsp.categories[i].category.name] = {'label':rsp.categories[i].category.name,
                        'num_cols':2, 'type':'simplegrid', 'headerValues':null,
                        'cellClasses':['', ''],
                        'noData':'No projects found',
                        'addTxt':'Add',
                        'addFn':'M.ciniki_projects_main.showEdit(\'M.ciniki_projects_main.showProjects();\',0,\'' + escape(rsp.categories[i].category.name) + '\');',
                        };
                }

                // Show the panel
                p.refresh();
                p.show(cb);
            });
    };

    this.showProject = function(cb, pid) {
        if( pid != null ) {
            this.project.project_id = pid;
        }
        this.project.data = {};
        var rsp = M.api.getJSONCb('ciniki.projects.projectGet', 
            {'tnid':M.curTenantID, 'project_id':this.project.project_id, 'children':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_projects_main.project;
                p.data = rsp.project;
                p.refresh();
                p.show(cb);
            });
    };

    this.showEdit = function(cb, pid, cat) {
        this.edit.reset();
        if( pid != null ) { this.edit.project_id = pid; }
        if( this.edit.project_id > 0 ) {
            var rsp = M.api.getJSONCb('ciniki.projects.projectGet', 
                {'tnid':M.curTenantID, 'project_id':this.edit.project_id, 'children':'no'}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_projects_main.edit;
                    p.data = rsp.project;
                    p.refresh();
                    p.show(cb);
                });
        } else {
            this.edit.reset();
            this.edit.data = this.edit.default_data;
            if( cat != null ) { this.edit.data.category = unescape(cat); }
            this.edit.refresh();
            this.edit.show(cb);
        }
    };

    this.saveProject = function() {
        if( this.edit.project_id > 0 ) {
            var c = this.edit.serializeForm('no');
            if( c != '' ) {
                var rsp = M.api.postJSONCb('ciniki.projects.projectUpdate', 
                    {'tnid':M.curTenantID, 'project_id':this.edit.project_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_projects_main.edit.close();
                    });
            } else {
                this.edit.close();
            }
        } else {
            var c = this.edit.serializeForm('yes');
            var rsp = M.api.postJSONCb('ciniki.projects.projectAdd', 
                {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_projects_main.edit.close();
            });
        }
    };

    this.deleteProject = function() {
        M.confirm("Are you sure you want to remove the project '" + this.edit.data.name + "'?",null,function() {
            var rsp = M.api.getJSONCb('ciniki.projects.projectDelete', 
                {'tnid':M.curTenantID, 
                'project_id':M.ciniki_projects_main.edit.project_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_projects_main.project.close();
                });
        });
    };
}
