-- =============================================================================
-- Generate Large Moodle Test Course
-- 300 students, 2 assignments, 3 quizzes
-- Due order: Quiz 1 → Quiz 2 → Assignment 1 → Quiz 3 → Assignment 2
-- Submission counts:
--   Quiz 1:        285 / 300  (nearly all, auto-marked)
--   Quiz 2:        250 / 300  (a few less, auto-marked)
--   Assignment 1:  270 / 300  (most, all graded)
--   Quiz 3:         10 / 300  (a few, not yet due, auto-marked)
--   Assignment 2:    8 / 300  (a few, not yet due, ungraded)
-- Additional:
--   280 students have m_user_lastaccess coherent with their latest submission
--   6 groups with random student assignment
--   Quiz 1 override: 10 random students +1 week
--   Quiz 2 override: same 10 + 10 more random students +1 week
--   Assignment 1 override: 15 random students +1 week
--   Assignment 2 override: 5 random students +1 week
-- NOTE: "Assignment 3" interpreted as Assignment 2 (only 2 assignments exist)
-- =============================================================================

DO $$
DECLARE
    v_course_id       bigint;
    v_course_ctx_id   bigint;
    v_enrol_id        bigint;
    v_student_role_id bigint;
    v_assign_mod_id   bigint;
    v_quiz_mod_id     bigint;
    v_section0_id     bigint;
    v_section1_id     bigint;
    v_sys_ctx_id      bigint;
    v_cat_ctx_id      bigint;
    v_category_id     bigint := 1;
    v_now             bigint;

    v_quiz1_id   bigint; v_quiz2_id   bigint; v_quiz3_id   bigint;
    v_assign1_id bigint; v_assign2_id bigint; v_assign3_id bigint;
    v_cm_quiz1   bigint; v_cm_quiz2   bigint; v_cm_quiz3   bigint;
    v_cm_assign1 bigint; v_cm_assign2 bigint; v_cm_assign3 bigint;
    v_ctx_quiz1  bigint; v_ctx_quiz2  bigint; v_ctx_quiz3  bigint;
    v_ctx_assign1 bigint; v_ctx_assign2 bigint; v_ctx_assign3 bigint;
    v_gi_quiz1   bigint; v_gi_quiz2   bigint; v_gi_quiz3   bigint;
    v_gi_assign1 bigint; v_gi_assign2 bigint; v_gi_assign3 bigint;

    v_quiz1_open   bigint; v_quiz1_close  bigint;
    v_quiz2_open   bigint; v_quiz2_close  bigint;
    v_assign1_open bigint; v_assign1_due  bigint;
    v_quiz3_open   bigint; v_quiz3_close  bigint;
    v_assign2_open bigint; v_assign2_due  bigint;
    v_assign3_open bigint; v_assign3_due  bigint;

    -- Groups
    v_groups        bigint[] := ARRAY[]::bigint[];
    v_group_id      bigint;

    -- Extension override student lists
    v_ext_quiz1       bigint[];   -- 10 random students
    v_ext_quiz2_extra bigint[];   -- 10 more (different from quiz1 set)
    v_ext_quiz2       bigint[];   -- quiz1 + extra = up to 20
    v_ext_assign1     bigint[];   -- 15 random students
    v_ext_assign2     bigint[];   -- 5 random students
    v_ext_assign3     bigint[];   -- 5 random students

    v_user_id         bigint;
    v_usage_id        bigint;
    v_subm_id         bigint;
    v_grade_val       numeric;
    v_time_subm       bigint;
    v_last_subm_time  bigint;
    v_users           bigint[] := ARRAY[]::bigint[];
    i                 int;
    v_uid             bigint;
	v_username        text;

    first_names text[] := ARRAY[
        'James','John','Robert','Michael','William','David','Richard','Joseph','Thomas','Charles',
        'Christopher','Daniel','Matthew','Anthony','Mark','Steven','Paul','Andrew','Joshua','Kenneth',
        'Kevin','Brian','George','Timothy','Ronald','Edward','Jason','Jeffrey','Ryan','Gary',
        'Jacob','Nicholas','Eric','Jonathan','Stephen','Larry','Justin','Scott','Brandon','Benjamin',
        'Samuel','Raymond','Gregory','Frank','Alexander','Patrick','Jack','Dennis','Aaron','Tyler',
        'Mary','Patricia','Jennifer','Linda','Barbara','Elizabeth','Susan','Jessica','Sarah','Karen',
        'Nancy','Lisa','Betty','Margaret','Sandra','Ashley','Dorothy','Kimberly','Emily','Donna',
        'Michelle','Carol','Amanda','Melissa','Deborah','Stephanie','Rebecca','Sharon','Laura','Cynthia',
        'Emma','Olivia','Sophia','Isabella','Charlotte','Amelia','Abigail','Evelyn','Mia','Harper',
        'Liam','Noah','Oliver','Elijah','Lucas','Mason','Logan','Ethan','Aiden','Jackson'
    ];
    last_names text[] := ARRAY[
        'Smith','Johnson','Williams','Brown','Jones','Garcia','Miller','Davis','Rodriguez','Martinez',
        'Hernandez','Lopez','Gonzalez','Wilson','Anderson','Thomas','Taylor','Moore','Jackson','Martin',
        'Lee','Perez','Thompson','White','Harris','Sanchez','Clark','Ramirez','Lewis','Robinson',
        'Walker','Young','Allen','King','Wright','Scott','Torres','Nguyen','Hill','Flores',
        'Green','Adams','Nelson','Baker','Hall','Rivera','Campbell','Mitchell','Carter','Roberts',
        'Phillips','Evans','Turner','Parker','Collins','Edwards','Stewart','Morris','Murphy','Cook',
        'Rogers','Morgan','Peterson','Cooper','Reed','Bailey','Bell','Gomez','Kelly','Howard',
        'Ward','Cox','Diaz','Richardson','Wood','Watson','Brooks','Bennett','Gray','James',
        'Reyes','Cruz','Hughes','Price','Myers','Long','Foster','Sanders','Ross','Morales',
        'Powell','Sullivan','Russell','Ortiz','Jenkins','Gutierrez','Perry','Butler','Barnes','Fisher'
    ];

BEGIN
    v_now := extract(epoch from now())::bigint;

    v_quiz1_open   := v_now - 86400*42;
    v_quiz1_close  := v_now - 86400*28;
    v_quiz2_open   := v_now - 86400*35;
    v_quiz2_close  := v_now - 86400*21;
    v_assign1_open := v_now - 86400*35;
    v_assign1_due  := v_now - 86400*14;
    v_quiz3_open   := v_now - 86400*7;
    v_quiz3_close  := v_now + 86400*7;
    v_assign2_open := v_now - 86400*7;
    v_assign2_due  := v_now + 86400*14;
    -- Assignment 3: opens in 2 weeks, due in 5 weeks (well into the future, no submissions expected)
    v_assign3_open := v_now + 86400*14;
    v_assign3_due  := v_now + 86400*35;

    SELECT id INTO v_sys_ctx_id      FROM m_context WHERE contextlevel = 10 LIMIT 1;
    SELECT id INTO v_cat_ctx_id      FROM m_context WHERE contextlevel = 40 AND instanceid = v_category_id LIMIT 1;
    SELECT id INTO v_student_role_id FROM m_role    WHERE shortname = 'student';
    SELECT id INTO v_assign_mod_id   FROM m_modules WHERE name = 'assign';
    SELECT id INTO v_quiz_mod_id     FROM m_modules WHERE name = 'quiz';

    -- ==========================================================================
    -- 1. COURSE
    -- ==========================================================================
    INSERT INTO m_course (
        category, sortorder, fullname, shortname, idnumber,
        summary, summaryformat, format, showgrades, newsitems,
        startdate, enddate, marker, maxbytes, legacyfiles, showreports,
        visible, visibleold, groupmode, groupmodeforce, defaultgroupingid,
        lang, theme, timecreated, timemodified,
        requested, enablecompletion, completionnotify, calendartype, cacherev,
        relativedatesmode, originalcourseid
    ) VALUES (
        v_category_id, 10000,
        'Large Test Course 301', 'LTC301', '',
        'Auto-generated test course.', 1,
        'topics', 1, 5,
        v_quiz1_open, v_assign2_due + 86400*60, 0, 0, 0, 0,
        1, 1, 0, 0, 0,
        '', '', v_now, v_now,
        0, 1, 0, 'gregorian', 0,
        0, null
    ) RETURNING id INTO v_course_id;

    INSERT INTO m_context (contextlevel, instanceid, depth, path, locked)
    VALUES (50, v_course_id, 3, '/placeholder', 0)
    RETURNING id INTO v_course_ctx_id;

    UPDATE m_context
    SET path = concat('/', v_sys_ctx_id, '/', COALESCE(v_cat_ctx_id::text, '1'), '/', v_course_ctx_id)
    WHERE id = v_course_ctx_id;

    INSERT INTO m_course_sections
        (course, section, name, summary, summaryformat, sequence, visible, availability, timemodified)
    VALUES (v_course_id, 0, '', '', 1, '', 1, null, v_now)
    RETURNING id INTO v_section0_id;

    INSERT INTO m_course_sections
        (course, section, name, summary, summaryformat, sequence, visible, availability, timemodified)
    VALUES (v_course_id, 1, 'Activities', '', 1, '', 1, null, v_now)
    RETURNING id INTO v_section1_id;

    INSERT INTO m_enrol (enrol, status, courseid, sortorder, timecreated, timemodified)
    VALUES ('manual', 0, v_course_id, 0, v_now, v_now)
    RETURNING id INTO v_enrol_id;

    -- ==========================================================================
    -- 2. CREATE 6 GROUPS
    -- ==========================================================================
    FOR i IN 1..6 LOOP
        INSERT INTO m_groups (
            courseid, name, description, descriptionformat,
            enrolmentkey, picture, timecreated, timemodified,
            idnumber, visibility, participation
        ) VALUES (
            v_course_id, 'Group ' || i, '', 1,
            null, 0, v_now, v_now,
            '', 0, 1
        ) RETURNING id INTO v_group_id;
        v_groups := array_append(v_groups, v_group_id);
    END LOOP;

    -- ==========================================================================
    -- 3. CREATE 300 STUDENTS + ENROL + ASSIGN TO RANDOM GROUP
    -- ==========================================================================
    FOR i IN 1..300 LOOP
	v_username := lpad(
    (mod(abs(('x' || substr(md5(i::text || random()::text), 1, 8))::bit(32)::int), 90000000) + 10000000)::text,
    8, '0'
);
        INSERT INTO m_user (
            auth, confirmed, policyagreed, deleted, suspended, mnethostid,
            username, password, idnumber,
            firstname, lastname, email,
            emailstop, phone1, phone2, institution, department, address,
            city, country, lang, theme, timezone,
            firstaccess, lastaccess, lastlogin, currentlogin, lastip,
            secret, picture, description, descriptionformat,
            mailformat, maildigest, maildisplay, autosubscribe, trackforums,
            timecreated, timemodified, trustbitmask,
            imagealt, alternatename, lastnamephonetic, firstnamephonetic,
            middlename, calendartype, moodlenetprofile
        ) VALUES (
            'manual', 1, 0, 0, 0, 1,
            v_username,
            'not-a-real-hash', '',
            first_names[1 + ((i - 1) % array_length(first_names, 1))],
            last_names [1 + (((i - 1) * 7) % array_length(last_names, 1))],
            v_username || '@example.com',
            0, '', '', '', '', '',
            'Auckland', 'NZ', 'en', '', '99',
            v_now - 86400*60, v_now - 86400, v_now - 86400, v_now - 86400, '',
            '', 0, '', 1,
            1, 0, 2, 1, 0,
            v_now - 86400*90, v_now, 0,
            null, null, null, null,
            null, 'gregorian', null
        ) RETURNING id INTO v_user_id;

        INSERT INTO m_user_enrolments
            (status, enrolid, userid, timestart, timeend, modifierid, timecreated, timemodified)
        VALUES (0, v_enrol_id, v_user_id, v_now - 86400*90, 0, 2, v_now, v_now);

        INSERT INTO m_role_assignments
            (roleid, contextid, userid, timemodified, modifierid, component, itemid, sortorder)
        VALUES (v_student_role_id, v_course_ctx_id, v_user_id, v_now, 2, '', 0, 0);

        -- Assign to a random group
        v_group_id := v_groups[1 + floor(random() * 6)::int];
        INSERT INTO m_groups_members (groupid, userid, timeadded, component, itemid)
        VALUES (v_group_id, v_user_id, v_now, '', 0);

        v_users := array_append(v_users, v_user_id);
    END LOOP;

    -- ==========================================================================
    -- 4. ACTIVITIES
    -- ==========================================================================

    INSERT INTO m_quiz (
        course, name, intro, introformat, timeopen, timeclose,
        preferredbehaviour, attempts, attemptonlast, grademethod,
        decimalpoints, questiondecimalpoints,
        reviewattempt, reviewcorrectness, reviewmarks,
        reviewspecificfeedback, reviewgeneralfeedback,
        reviewrightanswer, reviewoverallfeedback,
        questionsperpage, shuffleanswers, sumgrades, grade,
        timecreated, timemodified, timelimit,
        overduehandling, graceperiod, navmethod
    ) VALUES (
        v_course_id, 'Quiz 1', 'Quiz 1', 1, v_quiz1_open, v_quiz1_close,
        'deferredfeedback', 1, 0, 1, 2, -1,
        69888, 69888, 69888, 69888, 69888, 69888, 69888,
        1, 1, 100.0, 100.0, v_now, v_now, 0,
        'autoabandon', 0, 'free'
    ) RETURNING id INTO v_quiz1_id;

    INSERT INTO m_quiz (
        course, name, intro, introformat, timeopen, timeclose,
        preferredbehaviour, attempts, attemptonlast, grademethod,
        decimalpoints, questiondecimalpoints,
        reviewattempt, reviewcorrectness, reviewmarks,
        reviewspecificfeedback, reviewgeneralfeedback,
        reviewrightanswer, reviewoverallfeedback,
        questionsperpage, shuffleanswers, sumgrades, grade,
        timecreated, timemodified, timelimit,
        overduehandling, graceperiod, navmethod
    ) VALUES (
        v_course_id, 'Quiz 2', 'Quiz 2', 1, v_quiz2_open, v_quiz2_close,
        'deferredfeedback', 1, 0, 1, 2, -1,
        69888, 69888, 69888, 69888, 69888, 69888, 69888,
        1, 1, 100.0, 100.0, v_now, v_now, 0,
        'autoabandon', 0, 'free'
    ) RETURNING id INTO v_quiz2_id;

    INSERT INTO m_assign (
        course, name, intro, introformat, alwaysshowdescription,
        nosubmissions, submissiondrafts, sendnotifications, sendlatenotifications,
        sendstudentnotifications, duedate, allowsubmissionsfromdate, grade,
        timemodified, cutoffdate, requiresubmissionstatement, completionsubmit,
        teamsubmission, requireallteammemberssubmit, teamsubmissiongroupingid,
        blindmarking, revealidentities, attemptreopenmethod,
        maxattempts, markingworkflow, markingallocation,
        gradingduedate, hidegrader, activityformat, timelimit, submissionattachments
    ) VALUES (
        v_course_id, 'Assignment 1', 'Assignment 1', 1, 1,
        0, 0, 1, 1, 1, v_assign1_due, v_assign1_open, 100,
        v_now, 0, 0, 0, 0, 0, 0,
        0, 0, 'none', -1, 0, 0,
        v_assign1_due + 86400*7, 0, 0, 0, 0
    ) RETURNING id INTO v_assign1_id;

    INSERT INTO m_quiz (
        course, name, intro, introformat, timeopen, timeclose,
        preferredbehaviour, attempts, attemptonlast, grademethod,
        decimalpoints, questiondecimalpoints,
        reviewattempt, reviewcorrectness, reviewmarks,
        reviewspecificfeedback, reviewgeneralfeedback,
        reviewrightanswer, reviewoverallfeedback,
        questionsperpage, shuffleanswers, sumgrades, grade,
        timecreated, timemodified, timelimit,
        overduehandling, graceperiod, navmethod
    ) VALUES (
        v_course_id, 'Quiz 3', 'Quiz 3', 1, v_quiz3_open, v_quiz3_close,
        'deferredfeedback', 1, 0, 1, 2, -1,
        69888, 69888, 69888, 69888, 69888, 69888, 69888,
        1, 1, 100.0, 100.0, v_now, v_now, 0,
        'autoabandon', 0, 'free'
    ) RETURNING id INTO v_quiz3_id;

    INSERT INTO m_assign (
        course, name, intro, introformat, alwaysshowdescription,
        nosubmissions, submissiondrafts, sendnotifications, sendlatenotifications,
        sendstudentnotifications, duedate, allowsubmissionsfromdate, grade,
        timemodified, cutoffdate, requiresubmissionstatement, completionsubmit,
        teamsubmission, requireallteammemberssubmit, teamsubmissiongroupingid,
        blindmarking, revealidentities, attemptreopenmethod,
        maxattempts, markingworkflow, markingallocation,
        gradingduedate, hidegrader, activityformat, timelimit, submissionattachments
    ) VALUES (
        v_course_id, 'Assignment 2', 'Assignment 2', 1, 1,
        0, 0, 1, 1, 1, v_assign2_due, v_assign2_open, 100,
        v_now, 0, 0, 0, 0, 0, 0,
        0, 0, 'none', -1, 0, 0,
        v_assign2_due + 86400*7, 0, 0, 0, 0
    ) RETURNING id INTO v_assign2_id;

    -- Assignment 3 — future, not yet open, no submissions
    INSERT INTO m_assign (
        course, name, intro, introformat, alwaysshowdescription,
        nosubmissions, submissiondrafts, sendnotifications, sendlatenotifications,
        sendstudentnotifications, duedate, allowsubmissionsfromdate, grade,
        timemodified, cutoffdate, requiresubmissionstatement, completionsubmit,
        teamsubmission, requireallteammemberssubmit, teamsubmissiongroupingid,
        blindmarking, revealidentities, attemptreopenmethod,
        maxattempts, markingworkflow, markingallocation,
        gradingduedate, hidegrader, activityformat, timelimit, submissionattachments
    ) VALUES (
        v_course_id, 'Assignment 3', 'Assignment 3', 1, 1,
        0, 0, 1, 1, 1, v_assign3_due, v_assign3_open, 100,
        v_now, 0, 0, 0, 0, 0, 0,
        0, 0, 'none', -1, 0, 0,
        v_assign3_due + 86400*7, 0, 0, 0, 0
    ) RETURNING id INTO v_assign3_id;

    -- ==========================================================================
    -- 5. COURSE MODULES
    -- ==========================================================================
    INSERT INTO m_course_modules (
        course, module, instance, section, idnumber, added, score, indent,
        visible, visibleold, groupmode, groupingid, completion,
        completiongradeitemnumber, completionview, completionexpected,
        showdescription, availability, deletioninprogress,
        visibleoncoursepage, completionpassgrade, downloadcontent, lang
    ) VALUES (v_course_id, v_quiz_mod_id, v_quiz1_id, v_section1_id, null, v_now, 0, 0,
              1, 1, 0, 0, 0, null, 0, 0, 0, null, 0, 1, 0, 1, null)
    RETURNING id INTO v_cm_quiz1;

    INSERT INTO m_course_modules (
        course, module, instance, section, idnumber, added, score, indent,
        visible, visibleold, groupmode, groupingid, completion,
        completiongradeitemnumber, completionview, completionexpected,
        showdescription, availability, deletioninprogress,
        visibleoncoursepage, completionpassgrade, downloadcontent, lang
    ) VALUES (v_course_id, v_quiz_mod_id, v_quiz2_id, v_section1_id, null, v_now, 0, 0,
              1, 1, 0, 0, 0, null, 0, 0, 0, null, 0, 1, 0, 1, null)
    RETURNING id INTO v_cm_quiz2;

    INSERT INTO m_course_modules (
        course, module, instance, section, idnumber, added, score, indent,
        visible, visibleold, groupmode, groupingid, completion,
        completiongradeitemnumber, completionview, completionexpected,
        showdescription, availability, deletioninprogress,
        visibleoncoursepage, completionpassgrade, downloadcontent, lang
    ) VALUES (v_course_id, v_assign_mod_id, v_assign1_id, v_section1_id, null, v_now, 0, 0,
              1, 1, 0, 0, 0, null, 0, 0, 0, null, 0, 1, 0, 1, null)
    RETURNING id INTO v_cm_assign1;

    INSERT INTO m_course_modules (
        course, module, instance, section, idnumber, added, score, indent,
        visible, visibleold, groupmode, groupingid, completion,
        completiongradeitemnumber, completionview, completionexpected,
        showdescription, availability, deletioninprogress,
        visibleoncoursepage, completionpassgrade, downloadcontent, lang
    ) VALUES (v_course_id, v_quiz_mod_id, v_quiz3_id, v_section1_id, null, v_now, 0, 0,
              1, 1, 0, 0, 0, null, 0, 0, 0, null, 0, 1, 0, 1, null)
    RETURNING id INTO v_cm_quiz3;

    INSERT INTO m_course_modules (
        course, module, instance, section, idnumber, added, score, indent,
        visible, visibleold, groupmode, groupingid, completion,
        completiongradeitemnumber, completionview, completionexpected,
        showdescription, availability, deletioninprogress,
        visibleoncoursepage, completionpassgrade, downloadcontent, lang
    ) VALUES (v_course_id, v_assign_mod_id, v_assign2_id, v_section1_id, null, v_now, 0, 0,
              1, 1, 0, 0, 0, null, 0, 0, 0, null, 0, 1, 0, 1, null)
    RETURNING id INTO v_cm_assign2;

	INSERT INTO m_course_modules (
    course, module, instance, section, idnumber, added, score, indent,
    visible, visibleold, groupmode, groupingid, completion,
    completiongradeitemnumber, completionview, completionexpected,
    showdescription, availability, deletioninprogress,
    visibleoncoursepage, completionpassgrade, downloadcontent, lang
) VALUES (v_course_id, v_assign_mod_id, v_assign3_id, v_section1_id, null, v_now, 0, 0,
          1, 1, 0, 0, 0, null, 0, 0, 0, null, 0, 1, 0, 1, null)
RETURNING id INTO v_cm_assign3;

UPDATE m_course_sections
SET sequence = concat(v_cm_quiz1,',',v_cm_quiz2,',',v_cm_assign1,',',v_cm_quiz3,',',v_cm_assign2,',',v_cm_assign3)
WHERE id = v_section1_id;

    -- ==========================================================================
    -- 6. MODULE CONTEXTS
    -- ==========================================================================
    INSERT INTO m_context (contextlevel, instanceid, depth, path, locked)
    VALUES (70, v_cm_quiz1, 4, '/placeholder', 0) RETURNING id INTO v_ctx_quiz1;
    UPDATE m_context SET path = concat('/',v_sys_ctx_id,'/',COALESCE(v_cat_ctx_id::text,'1'),'/',v_course_ctx_id,'/',v_ctx_quiz1) WHERE id = v_ctx_quiz1;

    INSERT INTO m_context (contextlevel, instanceid, depth, path, locked)
    VALUES (70, v_cm_quiz2, 4, '/placeholder', 0) RETURNING id INTO v_ctx_quiz2;
    UPDATE m_context SET path = concat('/',v_sys_ctx_id,'/',COALESCE(v_cat_ctx_id::text,'1'),'/',v_course_ctx_id,'/',v_ctx_quiz2) WHERE id = v_ctx_quiz2;

    INSERT INTO m_context (contextlevel, instanceid, depth, path, locked)
    VALUES (70, v_cm_quiz3, 4, '/placeholder', 0) RETURNING id INTO v_ctx_quiz3;
    UPDATE m_context SET path = concat('/',v_sys_ctx_id,'/',COALESCE(v_cat_ctx_id::text,'1'),'/',v_course_ctx_id,'/',v_ctx_quiz3) WHERE id = v_ctx_quiz3;

    INSERT INTO m_context (contextlevel, instanceid, depth, path, locked)
    VALUES (70, v_cm_assign1, 4, '/placeholder', 0) RETURNING id INTO v_ctx_assign1;
    UPDATE m_context SET path = concat('/',v_sys_ctx_id,'/',COALESCE(v_cat_ctx_id::text,'1'),'/',v_course_ctx_id,'/',v_ctx_assign1) WHERE id = v_ctx_assign1;

    INSERT INTO m_context (contextlevel, instanceid, depth, path, locked)
    VALUES (70, v_cm_assign2, 4, '/placeholder', 0) RETURNING id INTO v_ctx_assign2;
    UPDATE m_context SET path = concat('/',v_sys_ctx_id,'/',COALESCE(v_cat_ctx_id::text,'1'),'/',v_course_ctx_id,'/',v_ctx_assign2) WHERE id = v_ctx_assign2;

    INSERT INTO m_context (contextlevel, instanceid, depth, path, locked)
    VALUES (70, v_cm_assign3, 4, '/placeholder', 0) RETURNING id INTO v_ctx_assign3;
    UPDATE m_context SET path = concat('/',v_sys_ctx_id,'/',COALESCE(v_cat_ctx_id::text,'1'),'/',v_course_ctx_id,'/',v_ctx_assign3) WHERE id = v_ctx_assign3;

    -- ==========================================================================
    -- 7. GRADE ITEMS
    -- ==========================================================================
    INSERT INTO m_grade_items (
        courseid, categoryid, itemname, itemtype, itemmodule, iteminstance,
        itemnumber, idnumber, calculation, gradetype, grademax, grademin,
        scaleid, outcomeid, gradepass, multfactor, plusfactor,
        aggregationcoef, aggregationcoef2, sortorder, display, decimals,
        hidden, locked, locktime, needsupdate, timecreated, timemodified, weightoverride
    ) VALUES (v_course_id, null, null, 'course', null, null,
              null, null, null, 1, 100.0, 0.0, null, null,
              0.0, 1.0, 0.0, 0.0, 0.0, 1, 0, null,
              0, 0, 0, 0, v_now, v_now, 0);

    INSERT INTO m_grade_items (
        courseid, categoryid, itemname, itemtype, itemmodule, iteminstance,
        itemnumber, idnumber, calculation, gradetype, grademax, grademin,
        scaleid, outcomeid, gradepass, multfactor, plusfactor,
        aggregationcoef, aggregationcoef2, sortorder, display, decimals,
        hidden, locked, locktime, needsupdate, timecreated, timemodified, weightoverride
    ) VALUES (v_course_id, null, 'Quiz 1', 'mod', 'quiz', v_quiz1_id,
              0, null, null, 1, 100.0, 0.0, null, null,
              0.0, 1.0, 0.0, 0.0, 0.0, 2, 0, null,
              0, 0, 0, 0, v_now, v_now, 0)
    RETURNING id INTO v_gi_quiz1;

    INSERT INTO m_grade_items (
        courseid, categoryid, itemname, itemtype, itemmodule, iteminstance,
        itemnumber, idnumber, calculation, gradetype, grademax, grademin,
        scaleid, outcomeid, gradepass, multfactor, plusfactor,
        aggregationcoef, aggregationcoef2, sortorder, display, decimals,
        hidden, locked, locktime, needsupdate, timecreated, timemodified, weightoverride
    ) VALUES (v_course_id, null, 'Quiz 2', 'mod', 'quiz', v_quiz2_id,
              0, null, null, 1, 100.0, 0.0, null, null,
              0.0, 1.0, 0.0, 0.0, 0.0, 3, 0, null,
              0, 0, 0, 0, v_now, v_now, 0)
    RETURNING id INTO v_gi_quiz2;

    INSERT INTO m_grade_items (
        courseid, categoryid, itemname, itemtype, itemmodule, iteminstance,
        itemnumber, idnumber, calculation, gradetype, grademax, grademin,
        scaleid, outcomeid, gradepass, multfactor, plusfactor,
        aggregationcoef, aggregationcoef2, sortorder, display, decimals,
        hidden, locked, locktime, needsupdate, timecreated, timemodified, weightoverride
    ) VALUES (v_course_id, null, 'Assignment 1', 'mod', 'assign', v_assign1_id,
              0, null, null, 1, 100.0, 0.0, null, null,
              0.0, 1.0, 0.0, 0.0, 0.0, 4, 0, null,
              0, 0, 0, 0, v_now, v_now, 0)
    RETURNING id INTO v_gi_assign1;

    INSERT INTO m_grade_items (
        courseid, categoryid, itemname, itemtype, itemmodule, iteminstance,
        itemnumber, idnumber, calculation, gradetype, grademax, grademin,
        scaleid, outcomeid, gradepass, multfactor, plusfactor,
        aggregationcoef, aggregationcoef2, sortorder, display, decimals,
        hidden, locked, locktime, needsupdate, timecreated, timemodified, weightoverride
    ) VALUES (v_course_id, null, 'Quiz 3', 'mod', 'quiz', v_quiz3_id,
              0, null, null, 1, 100.0, 0.0, null, null,
              0.0, 1.0, 0.0, 0.0, 0.0, 5, 0, null,
              0, 0, 0, 0, v_now, v_now, 0)
    RETURNING id INTO v_gi_quiz3;

    INSERT INTO m_grade_items (
        courseid, categoryid, itemname, itemtype, itemmodule, iteminstance,
        itemnumber, idnumber, calculation, gradetype, grademax, grademin,
        scaleid, outcomeid, gradepass, multfactor, plusfactor,
        aggregationcoef, aggregationcoef2, sortorder, display, decimals,
        hidden, locked, locktime, needsupdate, timecreated, timemodified, weightoverride
    ) VALUES (v_course_id, null, 'Assignment 2', 'mod', 'assign', v_assign2_id,
              0, null, null, 1, 100.0, 0.0, null, null,
              0.0, 1.0, 0.0, 0.0, 0.0, 6, 0, null,
              0, 0, 0, 0, v_now, v_now, 0)
    RETURNING id INTO v_gi_assign2;

    INSERT INTO m_grade_items (
        courseid, categoryid, itemname, itemtype, itemmodule, iteminstance,
        itemnumber, idnumber, calculation, gradetype, grademax, grademin,
        scaleid, outcomeid, gradepass, multfactor, plusfactor,
        aggregationcoef, aggregationcoef2, sortorder, display, decimals,
        hidden, locked, locktime, needsupdate, timecreated, timemodified, weightoverride
    ) VALUES (v_course_id, null, 'Assignment 3', 'mod', 'assign', v_assign3_id,
              0, null, null, 1, 100.0, 0.0, null, null,
              0.0, 1.0, 0.0, 0.0, 0.0, 7, 0, null,
              0, 0, 0, 0, v_now, v_now, 0)
    RETURNING id INTO v_gi_assign3;

    -- ==========================================================================
    -- 8. SUBMISSIONS, ATTEMPTS, GRADES  +  LASTACCESS TRACKING
    -- ==========================================================================
    FOR i IN 1..300 LOOP
        v_user_id        := v_users[i];
        v_last_subm_time := 0;

        -- Quiz 1: 285/300 submit
        IF i <= 285 THEN
            v_grade_val := round((40 + random() * 60)::numeric, 2);
            v_time_subm := v_quiz1_open + (random() * (v_quiz1_close - v_quiz1_open - 600))::bigint;

            INSERT INTO m_question_usages (component, contextid, preferredbehaviour)
            VALUES ('mod_quiz', v_ctx_quiz1, 'deferredfeedback')
            RETURNING id INTO v_usage_id;

            INSERT INTO m_quiz_attempts (
                uniqueid, quiz, userid, attempt, sumgrades,
                timestart, timefinish, timemodified, layout, preview,
                currentpage, state, timecheckstate,
                timemodifiedoffline, gradednotificationsenttime
            ) VALUES (
                v_usage_id, v_quiz1_id, v_user_id, 1, v_grade_val,
                v_time_subm, v_time_subm + 600, v_time_subm + 600, '0', 0,
                0, 'finished', null,
                0, null
            );

            INSERT INTO m_quiz_grades (quiz, userid, grade, timemodified)
            VALUES (v_quiz1_id, v_user_id, v_grade_val, v_time_subm + 600);

            INSERT INTO m_grade_grades (
                itemid, userid, rawgrade, rawgrademax, rawgrademin,
                finalgrade, hidden, locked, locktime, exported, overridden, excluded,
                timecreated, timemodified, aggregationstatus, aggregationweight
            ) VALUES (v_gi_quiz1, v_user_id, v_grade_val, 100, 0,
                      v_grade_val, 0, 0, 0, 0, 0, 0,
                      v_time_subm, v_time_subm + 600, 'unknown', null);

            IF v_time_subm > v_last_subm_time THEN v_last_subm_time := v_time_subm; END IF;
        END IF;

        -- Quiz 2: 250/300 submit
        IF i <= 250 THEN
            v_grade_val := round((40 + random() * 60)::numeric, 2);
            v_time_subm := v_quiz2_open + (random() * (v_quiz2_close - v_quiz2_open - 600))::bigint;

            INSERT INTO m_question_usages (component, contextid, preferredbehaviour)
            VALUES ('mod_quiz', v_ctx_quiz2, 'deferredfeedback')
            RETURNING id INTO v_usage_id;

            INSERT INTO m_quiz_attempts (
                uniqueid, quiz, userid, attempt, sumgrades,
                timestart, timefinish, timemodified, layout, preview,
                currentpage, state, timecheckstate,
                timemodifiedoffline, gradednotificationsenttime
            ) VALUES (
                v_usage_id, v_quiz2_id, v_user_id, 1, v_grade_val,
                v_time_subm, v_time_subm + 600, v_time_subm + 600, '0', 0,
                0, 'finished', null,
                0, null
            );

            INSERT INTO m_quiz_grades (quiz, userid, grade, timemodified)
            VALUES (v_quiz2_id, v_user_id, v_grade_val, v_time_subm + 600);

            INSERT INTO m_grade_grades (
                itemid, userid, rawgrade, rawgrademax, rawgrademin,
                finalgrade, hidden, locked, locktime, exported, overridden, excluded,
                timecreated, timemodified, aggregationstatus, aggregationweight
            ) VALUES (v_gi_quiz2, v_user_id, v_grade_val, 100, 0,
                      v_grade_val, 0, 0, 0, 0, 0, 0,
                      v_time_subm, v_time_subm + 600, 'unknown', null);

            IF v_time_subm > v_last_subm_time THEN v_last_subm_time := v_time_subm; END IF;
        END IF;

        -- Assignment 1: 270/300 submit, all graded
        IF i <= 270 THEN
            v_grade_val := round((40 + random() * 60)::numeric, 2);
            v_time_subm := v_assign1_open + (random() * (v_assign1_due - v_assign1_open))::bigint;

            INSERT INTO m_assign_submission (
                assignment, userid, timecreated, timemodified,
                status, groupid, attemptnumber, latest
            ) VALUES (
                v_assign1_id, v_user_id, v_time_subm, v_time_subm,
                'submitted', 0, 0, 1
            ) RETURNING id INTO v_subm_id;

            INSERT INTO m_assignsubmission_onlinetext
                (assignment, submission, onlinetext, onlineformat)
            VALUES (v_assign1_id, v_subm_id, 'Student submission for Assignment 1.', 1);

            INSERT INTO m_assign_grades (
                assignment, userid, timecreated, timemodified, grader, grade, attemptnumber
            ) VALUES (
                v_assign1_id, v_user_id,
                v_assign1_due + 86400, v_assign1_due + 86400, 2, v_grade_val, 0
            );

            INSERT INTO m_grade_grades (
                itemid, userid, rawgrade, rawgrademax, rawgrademin,
                finalgrade, hidden, locked, locktime, exported, overridden, excluded,
                timecreated, timemodified, aggregationstatus, aggregationweight
            ) VALUES (v_gi_assign1, v_user_id, v_grade_val, 100, 0,
                      v_grade_val, 0, 0, 0, 0, 0, 0,
                      v_time_subm, v_assign1_due + 86400, 'unknown', null);

            IF v_time_subm > v_last_subm_time THEN v_last_subm_time := v_time_subm; END IF;
        END IF;

        -- Quiz 3: 10/300 submit (early, not yet due)
        IF i <= 10 THEN
            v_grade_val := round((40 + random() * 60)::numeric, 2);
            v_time_subm := v_quiz3_open + (random() * (v_now - v_quiz3_open - 600))::bigint;

            INSERT INTO m_question_usages (component, contextid, preferredbehaviour)
            VALUES ('mod_quiz', v_ctx_quiz3, 'deferredfeedback')
            RETURNING id INTO v_usage_id;

            INSERT INTO m_quiz_attempts (
                uniqueid, quiz, userid, attempt, sumgrades,
                timestart, timefinish, timemodified, layout, preview,
                currentpage, state, timecheckstate,
                timemodifiedoffline, gradednotificationsenttime
            ) VALUES (
                v_usage_id, v_quiz3_id, v_user_id, 1, v_grade_val,
                v_time_subm, v_time_subm + 600, v_time_subm + 600, '0', 0,
                0, 'finished', null,
                0, null
            );

            INSERT INTO m_quiz_grades (quiz, userid, grade, timemodified)
            VALUES (v_quiz3_id, v_user_id, v_grade_val, v_time_subm + 600);

            INSERT INTO m_grade_grades (
                itemid, userid, rawgrade, rawgrademax, rawgrademin,
                finalgrade, hidden, locked, locktime, exported, overridden, excluded,
                timecreated, timemodified, aggregationstatus, aggregationweight
            ) VALUES (v_gi_quiz3, v_user_id, v_grade_val, 100, 0,
                      v_grade_val, 0, 0, 0, 0, 0, 0,
                      v_time_subm, v_time_subm + 600, 'unknown', null);

            IF v_time_subm > v_last_subm_time THEN v_last_subm_time := v_time_subm; END IF;
        END IF;

        -- Assignment 2: 8/300 submit (early, not yet due, ungraded)
        IF i <= 8 THEN
            v_time_subm := v_assign2_open + (random() * (v_now - v_assign2_open))::bigint;

            INSERT INTO m_assign_submission (
                assignment, userid, timecreated, timemodified,
                status, groupid, attemptnumber, latest
            ) VALUES (
                v_assign2_id, v_user_id, v_time_subm, v_time_subm,
                'submitted', 0, 0, 1
            ) RETURNING id INTO v_subm_id;

            INSERT INTO m_assignsubmission_onlinetext
                (assignment, submission, onlinetext, onlineformat)
            VALUES (v_assign2_id, v_subm_id, 'Student early submission for Assignment 2.', 1);

            IF v_time_subm > v_last_subm_time THEN v_last_subm_time := v_time_subm; END IF;
        END IF;

        -- lastaccess: 280 students with at least one submission
        -- All students i=1..285 submitted Quiz 1, so i=1..280 are guaranteed a time
        IF i <= 280 AND v_last_subm_time > 0 THEN
            INSERT INTO m_user_lastaccess (userid, courseid, timeaccess)
            VALUES (
                v_user_id,
                v_course_id,
                -- last access = submission time + random 0-4 hours of continued browsing
                v_last_subm_time + (random() * 14400)::bigint
            );
        END IF;

    END LOOP;

    -- ==========================================================================
    -- 9. OVERRIDES
    -- ==========================================================================

    -- Pick 10 random students for Quiz 1 extension
    SELECT array_agg(u) INTO v_ext_quiz1
    FROM (SELECT unnest(v_users) u ORDER BY random() LIMIT 10) x;

    -- Pick 10 *additional* students (not in Quiz 1 set) for Quiz 2 extension
    SELECT array_agg(u) INTO v_ext_quiz2_extra
    FROM (
        SELECT u FROM unnest(v_users) u
        WHERE u != ALL(v_ext_quiz1)
        ORDER BY random()
        LIMIT 10
    ) x;
    v_ext_quiz2 := v_ext_quiz1 || v_ext_quiz2_extra;

    -- Pick 15 random students for Assignment 1 extension
    SELECT array_agg(u) INTO v_ext_assign1
    FROM (SELECT unnest(v_users) u ORDER BY random() LIMIT 15) x;

    -- Pick 5 random students for Assignment 2 extension
    SELECT array_agg(u) INTO v_ext_assign2
    FROM (SELECT unnest(v_users) u ORDER BY random() LIMIT 5) x;

    -- Pick 5 random students for Assignment 3 extension
    SELECT array_agg(u) INTO v_ext_assign3
    FROM (SELECT unnest(v_users) u ORDER BY random() LIMIT 5) x;

    -- Quiz 1 overrides — extend timeclose by 1 week
    FOREACH v_uid IN ARRAY v_ext_quiz1 LOOP
        INSERT INTO m_quiz_overrides
            (quiz, groupid, userid, timeopen, timeclose, timelimit, attempts, password)
        VALUES
            (v_quiz1_id, null, v_uid, null, v_quiz1_close + 86400*7, null, null, null);
    END LOOP;

    -- Quiz 2 overrides — same 10 + 10 more, extend timeclose by 1 week
    FOREACH v_uid IN ARRAY v_ext_quiz2 LOOP
        INSERT INTO m_quiz_overrides
            (quiz, groupid, userid, timeopen, timeclose, timelimit, attempts, password)
        VALUES
            (v_quiz2_id, null, v_uid, null, v_quiz2_close + 86400*7, null, null, null);
    END LOOP;

    -- Assignment 1 overrides — extend duedate by 1 week
    FOREACH v_uid IN ARRAY v_ext_assign1 LOOP
        INSERT INTO m_assign_overrides
            (assignid, groupid, userid, sortorder, allowsubmissionsfromdate, duedate, cutoffdate, timelimit)
        VALUES
            (v_assign1_id, null, v_uid, null, null, v_assign1_due + 86400*7, null, null);
    END LOOP;

    -- Assignment 2 overrides — extend duedate by 1 week
    FOREACH v_uid IN ARRAY v_ext_assign2 LOOP
        INSERT INTO m_assign_overrides
            (assignid, groupid, userid, sortorder, allowsubmissionsfromdate, duedate, cutoffdate, timelimit)
        VALUES
            (v_assign2_id, null, v_uid, null, null, v_assign2_due + 86400*7, null, null);
    END LOOP;

    -- Assignment 3 overrides — extend duedate by 1 week
    FOREACH v_uid IN ARRAY v_ext_assign3 LOOP
        INSERT INTO m_assign_overrides
            (assignid, groupid, userid, sortorder, allowsubmissionsfromdate, duedate, cutoffdate, timelimit)
        VALUES
            (v_assign3_id, null, v_uid, null, null, v_assign3_due + 86400*7, null, null);
    END LOOP;

    -- ==========================================================================
    RAISE NOTICE '=== Done ===';
    RAISE NOTICE 'Course ID        : %', v_course_id;
    RAISE NOTICE 'Quiz 1 ID / CM   : % / %', v_quiz1_id,   v_cm_quiz1;
    RAISE NOTICE 'Quiz 2 ID / CM   : % / %', v_quiz2_id,   v_cm_quiz2;
    RAISE NOTICE 'Assignment 1 / CM: % / %', v_assign1_id, v_cm_assign1;
    RAISE NOTICE 'Quiz 3 ID / CM   : % / %', v_quiz3_id,   v_cm_quiz3;
    RAISE NOTICE 'Assignment 2 / CM: % / %', v_assign2_id, v_cm_assign2;
    RAISE NOTICE 'Assignment 3 / CM: % / %', v_assign3_id, v_cm_assign3;
    RAISE NOTICE 'Groups created   : %', array_length(v_groups, 1);
    RAISE NOTICE 'Quiz 1 overrides : %', array_length(v_ext_quiz1, 1);
    RAISE NOTICE 'Quiz 2 overrides : %', array_length(v_ext_quiz2, 1);
    RAISE NOTICE 'Assign 1 overrides: %', array_length(v_ext_assign1, 1);
    RAISE NOTICE 'Assign 2 overrides: %', array_length(v_ext_assign2, 1);
    RAISE NOTICE 'Assign 3 overrides: %', array_length(v_ext_assign3, 1);

END $$;