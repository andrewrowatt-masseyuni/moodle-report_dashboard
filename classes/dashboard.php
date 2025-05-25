<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace report_dashboard;

/**
 * Class dashboard
 *
 * @package    report_dashboard
 * @copyright  2025 Andrew Rowatt <A.J.Rowatt@massey.ac.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dashboard {

    /**
     * Gets the Master SQL statement and appended the specific dataset required
     *
     * @param string $subquery
     * @return string
     */
    public static function get_master_sql(string $subquery): string {
        $mastersql = get_config('report_dashboard', 'mastersql');
        $mastersql .= "select * from $subquery";

        return $mastersql;
    }

    /**
     * Simple helper function to get an item from a dataset by id. Assumes id is unique within the dataset.
     *
     * @param array $dataset
     * @param int $id
     * @return array
     */
    public static function get_item_by_id(array $dataset, int $id): array {
        $retval = [];

        foreach ($dataset as $item) {
            if ($item->id == $id) {
                $retval = (array)$item;
            }
        }

        return $retval;
    }

    /**
     * Get course groups with count of members
     *
     * @param int $courseid
     * @return array
     */
    public static function get_groups(int $courseid): array {
        global $DB, $USER;
        $data = $DB->get_records_sql(self::get_master_sql('get_groups'), ['course_id' => $courseid, 'user_id' => $USER->id, 'exclude_cmids' => '']);
        return $data;
    }

    public static function get_cohort_groups(int $courseid): array {
        global $DB, $USER;
        $data = $DB->get_records_sql(self::get_master_sql('get_cohort_groups'), ['course_id' => $courseid, 'user_id' => $USER->id, 'exclude_cmids' => '']);
        return $data;
    }

    public static function get_user_assessments(int $courseid, string $hiddencmids): array {
        // ... Hidden assessments are excluded!

        global $DB, $USER;
        $data = $DB->get_records_sql(self::get_master_sql('get_user_assessments'), ['course_id' => $courseid, 'user_id' => $USER->id, 'exclude_cmids' => $hiddencmids]);
        return $data;
    }

    public static function get_assessments(int $courseid): array {
        global $DB, $USER;
        $data = $DB->get_records_sql(self::get_master_sql('get_assessments'), ['course_id' => $courseid, 'user_id' => $USER->id, 'exclude_cmids' => '']);
        return $data;
    }

    public static function get_user_early_engagements(int $courseid, string $hiddencmids): array {
        // ... Hidden early engagement activites are excluded!

        global $DB, $USER;
        $data = $DB->get_records_sql(self::get_master_sql('get_user_early_engagements'), ['course_id' => $courseid, 'user_id' => $USER->id, 'exclude_cmids' => $hiddencmids]);
        return $data;
    }

    public static function get_early_engagements(int $courseid): array {
        global $DB, $USER;
        $data = $DB->get_records_sql(self::get_master_sql('get_early_engagements'), ['course_id' => $courseid, 'user_id' => $USER->id, 'exclude_cmids' => '']);
        return $data;
    }

    /**
     * Get the assessment statuses
     *
     * @return array
     */
    public static function get_assessment_statuses(): array {
        return [
            'notdue' => get_string('assessmentstatus_notdue', 'report_dashboard'),
            'submitted' => get_string('assessmentstatus_submitted', 'report_dashboard'),
            'overdue' => get_string('assessmentstatus_overdue', 'report_dashboard'),
            'passed' => get_string('assessmentstatus_passed', 'report_dashboard'),
            'failed' => get_string('assessmentstatus_failed', 'report_dashboard'),
            'extension' => get_string('assessmentstatus_extension', 'report_dashboard'),
            'graded' => get_string('assessmentstatus_graded', 'report_dashboard'),
        ];
    }

    /**
     * Get the earlyengagement statuses
     *
     * @return array
     */
    public static function get_earlyengagement_statuses(): array {
        return [
            'notdue' => get_string('earlyengagementstatus_notdue', 'report_dashboard'),
            'completed' => get_string('earlyengagementstatus_completed', 'report_dashboard'),
            'overdue' => get_string('earlyengagementstatus_overdue', 'report_dashboard'),
            'notcompleted' => get_string('earlyengagementstatus_notcompleted', 'report_dashboard'),
        ];
    }

    public static function get_user_dataset(int $courseid): array {
        global $DB, $USER;
        $data = $DB->get_records_sql(self::get_master_sql('get_user_dataset'), ['course_id' => $courseid, 'user_id' => $USER->id, 'exclude_cmids' => '']);
        return $data;
    }


    /**
     * Returns the default get_default_mastersql
     *
     * This should only be used for PHPUnit testing purposes (I think).
     *
     * @return string
     */
    public static function get_default_mastersql(): string {
        return "
        with
vars as (select :course_id::int as course_id, :user_id::int as userid, :exclude_cmids::text as exclude_cmids)
,roles as (
	with contexts as (
		select ctx.id as context_id from mdl_context ctx
		cross join vars v
		where ctx.id::text in 
		(select unnest(string_to_array(mdl_context.path,'/')) from mdl_context where mdl_context.contextlevel = 50 and mdl_context.instanceid = v.course_id)
	)
	--select * from contexts
	,user_roles as (
		select distinct r.shortname as role from contexts ctx
		cross join vars v
		join mdl_role_assignments ra on ra.contextid = ctx.context_id and ra.userid = v.userid
		join mdl_role r on r.id = ra.roleid
	)
	--select * from user_roles
	,admin_roles as (
		select distinct r.shortname as role from mdl_role r
		cross join vars v
		join mdl_role_context_levels rcl on rcl.roleid = r.id
		where v.userid::text in (
			select unnest(string_to_array(mc.value,',')) from mdl_config mc where mc.name='siteadmins'
		)
	)
	--select * from admin_roles
	,roles as (
		select distinct * from user_roles
		union select distinct * from admin_roles order by 1
	)
	select * from roles
)
--select * from roles

,students1 as (
	with q1 as (
		select distinct
		u.id as userid,
		trim(u.username) as username,
		u.firstname as firstname,
		u.lastname as lastname,
		u.email as email,
		case when ula.id is null then -1 else ula.timeaccess end as lastaccessed_timestamp
		from mdl_user u
		cross join vars v
		join mdl_role_assignments ra on ra.userid = u.id and ra.roleid = 5 
		join mdl_context ctx on ctx.id = ra.contextid and ctx.contextlevel = 50 and ctx.instanceid = v.course_id
		left join mdl_user_lastaccess ula on ula.courseid = v.course_id and ula.userid = u.id
		where u.username ~ '^\d{8}'
	)

	select ROW_NUMBER() OVER(order by username, firstname, lastname) as id,
	q1.*
	
	from q1

	order by username, firstname, lastname
)
--select * from students1

,get_cohort_groups as (
	select
	ROW_NUMBER() OVER(order by g.name, g.id) as id,
	g.id as groupid,
	g.name as groupname,
	g.description as groupdescription,
	count(distinct gm.id) as membercount
	
	from vars v
	join mdl_groups g on g.courseid = v.course_id
	join mdl_enrol e on e.courseid = v.course_id and e.enrol = 'meta' and e.customint2 = g.id
	left join mdl_groups_members gm on gm.groupid = g.id
	left join students1 u on u.userid = gm.userid

	where u.userid is not null

	group by 2,3,4

	having count(distinct gm.id) > 0

	order by g.name, g.id
)

,get_groups as (
	select
	ROW_NUMBER() OVER(order by g.name, g.id) as id,
	g.id as groupid,
	g.name as groupname,
	g.description as groupdescription,
	count(distinct gm.id) as membercount
	
	from vars v
	join mdl_groups g on g.courseid = v.course_id
	left join mdl_groups_members gm on gm.groupid = g.id
	left join get_cohort_groups cg on cg.groupid = g.id
	left join students1 u on u.userid = gm.userid

	where cg.id is null and u.userid is not null

	group by 2,3,4

	having count(distinct gm.id) > 0

	order by g.name, g.id
)

,students2 as (
	select s1.*,
	array_to_string(array_agg(distinct g.id order by g.id), ', ') as groups,
	array_to_string(array_agg(distinct gc.id order by gc.id), ', ') as cohortgroups
	from students1 s1
	cross join vars v
	left join mdl_groups_members gmg on gmg.userid = s1.userid
	left join get_groups g on g.groupid = gmg.groupid

	left join mdl_groups_members gmc on gmc.userid = s1.userid
	left join get_cohort_groups gc on gc.groupid = gmc.groupid
	group by 1,2,3,4,5,6,7
)
--select * from students2
,previous_enrolments as (
	with current_courses as (
		select c.id as course_id, c.idnumber
			from vars v
			join mdl_course c on c.id = v.course_id
			where c.idnumber != ''
	
			union
			select c.id as course_id, c.idnumber
			from vars v
			join mdl_enrol e on e.enrol = 'meta' and e.courseid = v.course_id
			join mdl_course c on c.id = e.customint1 and c.idnumber != ''
	)
	,modules as (
		select distinct left(c.idnumber,6) as module
		from current_courses c
		where c.idnumber ~ '^.{6}_\d{4}_.{4}_.*$'
	)
	,previous_offerings as (
		select c.id as course_id, c.idnumber, substring(c.idnumber,8,4) as year, substring(c.idnumber,13,2) as semester
		from mdl_course c
		left join current_courses cc on cc.course_id = c.id
		cross join modules m
		where
		cc.course_id is null /* exclude current courses */
		and
		c.idnumber ~ concat('^',m.module,'_\d{4}_.{4}_....*$')
		
	)
	,previous_offering_students as (
		select distinct u.username, c.year, c.semester
		from previous_offerings c
		JOIN mdl_context AS ctx ON c.course_id = ctx.instanceid and ctx.contextlevel = 50
		JOIN mdl_role_assignments AS ra ON ra.contextid = ctx.id and ra.roleid = 5 /* student */
		JOIN mdl_user AS u ON u.id = ra.userid and u.username ~ '^\d{8}$'
	)
	,previous_offering_student_summary as (
		select distinct
		c.username, 
		string_agg(concat(c.year,' ', c.semester), ', ' order by c.year desc, c.semester desc) as previous_enrolments,
		count(*) as previous_enrolment_count
		from previous_offering_students c
	
		group by 1
	)
	--select * from previous_offerings
	--select * from previous_offering_students
	select * from previous_offering_student_summary
)
--select * from previous_enrolments
,students3 as (
	select s2.*,
	case
		when r.role is not null then
		case
			when id_ethicity.data ~ '^Māori$' or id_ethicity.data ~ '/Māori$' or id_ethicity.data ~ '^Māori/'
				then 1
			else 0
		end
	else 0 end as maori,
	case
		when r.role is not null then
		case
			when id_ethicity.data ~ 'Pacific' or id_ethicity.data ~ 'Niuean' or id_ethicity.data ~ 'Samoan' or id_ethicity.data ~ 'Fijian' or id_ethicity.data ~ 'Tongan' or id_ethicity.data ~ 'Cook Island'
				then 1
			else 0
		end
	else 0 end	as pacific,
	case when id_international is not null then 1 else 0 end as international,
	case when (id_totalcreditsearned.data='' or id_totalcreditsearned.data::decimal = 0) then 1 else 0 end as new /* Inferred as New to Massey */,
	case when pe.previous_enrolments is null then '' else pe.previous_enrolments end as previous_enrolments
	from students2 s2
	cross join vars v
	left join mdl_user_info_data id_ethicity on id_ethicity.userid=s2.userid and id_ethicity.fieldid = 18
	left join mdl_user_info_data id_totalcreditsearned on id_totalcreditsearned.userid=s2.userid and id_totalcreditsearned.fieldid = 12
	left join mdl_user_info_data id_international on id_international.userid=s2.userid and id_international.fieldid = 19 and id_international.data = 'Y'
	left join roles r on r.role = 'priority_group_support'
	left join previous_enrolments pe on pe.username = s2.username
)
--select * from students3

,get_user_dataset as (
	select
	s.*
	from students3 s
	order by id
)
,excluded_cmids as (
	select r.id::int
	from vars v, regexp_split_to_table(trim(concat('0 ',v.exclude_cmids)),' ') r(id)
)

,activity as (
with q1 as (
	select cm.id as cmid, cm.instance as iteminstance, m.name as itemmodule,coalesce(a1.name,a2.name,'[Unknown]') as name,coalesce(a1.duedate,a2.timeclose,null) as activity_duedate_epoch,
	gi.id as grade_item_id, gi.iteminfo, gi.idnumber as activity_idnumber,gi.gradepass
	from mdl_course_modules cm
	left join excluded_cmids xcm on xcm.id = cm.id
	cross join vars v
	join mdl_modules m on m.id = cm.module
	left join mdl_assign a1 on a1.id = cm.instance and m.name='assign'
	left join mdl_quiz   a2 on a2.id = cm.instance and m.name='quiz'
	left join mdl_grade_items gi on gi.courseid = v.course_id and ((gi.itemmodule = 'assign' and gi.iteminstance = a1.id) or (gi.itemmodule = 'quiz' and gi.iteminstance = a2.id))
	where
	cm.course = v.course_id
	and
	(m.name in ('assign','quiz'))
	and
	xcm.id is null
)
	select
	*,
	ROW_NUMBER() OVER(order by activity_duedate_epoch,cmid) as activity_row_index
	from q1
	where activity_duedate_epoch != 0
)

--select * from activity

,get_assessments as (
	select
		activity_row_index as id,
		cmid
		from activity
		order by activity_row_index
)


,get_user_assessments as (
	with student_activity as (
		with q1 as (
			select s.userid, s.id as user_row_index,
			a.*
			from students1 s
			cross join activity a
		)
		
		select * from q1
	)
	--select * from student_activity
	,student_activity_grade as (
		with q1 as (
			select sa.*,g.finalgrade, ((g.finalgrade / g.rawgrademax) * 100)::int as finalgrade_percent
			from student_activity sa
			left join mdl_grade_grades g on g.itemid = sa.grade_item_id and g.userid = sa.userid
		)
		select * from q1
	)
	--select * from student_activity_grade
	,student_activity_grade_duedate as (
		with q1 as (
			select s.*
			from student_activity_grade s
		)
		,q2 as (
			select q1.*,
			case
				when uf.extensionduedate is not null and uf.extensionduedate <> 0 then uf.extensionduedate
				else activity_duedate_epoch
			end as student_duedate_epoch,
			case
				when uf.extensionduedate is not null and uf.extensionduedate <> 0 then 'Yes'
				else 'No'
			end as student_duedate_extension
			
			from q1
			left join mdl_assign_user_flags uf on uf.assignment = q1.iteminstance and uf.userid = q1.userid
			/*
			left join assign_user_overrides
			*/
			where q1.itemmodule='assign'
		)
		,q3 as (
			select q1.*,
			case
			when coalesce(o.timeclose,0) != 0 then o.timeclose else activity_duedate_epoch::bigint end as student_duedate_epoch,
			case
			when coalesce(o.timeclose,0) != 0 then 'Yes' else 'No' end as student_duedate_extension
			from q1
			left join mdl_quiz_overrides o on o.quiz = q1.iteminstance and o.userid=q1.userid
			where q1.itemmodule='quiz'
			/*select * from mdl_quiz_overrides where userid=96746*/
		)
		select * from q2
		union select * from q3
	)
	--select * from student_activity_grade_duedate where userid=96746
	,student_activity_grade_duedate_status as (
		/* assumes just assign & quiz*/
		with q1 as (
			select * from student_activity_grade_duedate
		)
		,q2 as (
			/* assign */
			select q1.*,
			sub.status as status_raw
			from q1
			left join mdl_assign_submission sub on sub.userid = q1.userid and sub.assignment = q1.iteminstance
			where q1.itemmodule = 'assign'
		),
		q3 as (
			/* quiz */
			select distinct on (q1.*,sub.state)
			q1.*,
			sub.state as status_raw
			from q1
			left join mdl_quiz_attempts sub on sub.userid = q1.userid and sub.quiz = q1.iteminstance
			where q1.itemmodule = 'quiz'
			order by q1.*,sub.state,sub.sumgrades desc
		)
		,q4 as (
			/* combine */
			select * from q2
			union select * from q3
		)
		,q5 as (
			/* transform status */
			select q4.*,
			case
				when q4.finalgrade is not null then -- q4.finalgrade_percent || ' ' ||
				case
					when q4.iteminfo is null or q4.iteminfo = '' or q4.iteminfo = 'marking_category0' then
						case
							when q4.gradepass = 0.0 and q4.finalgrade_percent >= 50 then 'passed'
							when q4.gradepass != 0.0 and q4.finalgrade >= q4.gradepass then 'passed'
							else 'failed'
						end
					/* Example alternative via iteminfo:
							Failed = 59% or less / Just passed = 79% or less / Passed = 80% or higher
					*/
					when q4.iteminfo = 'marking_category1' then
						case
							when q4.finalgrade_percent >= 80 then 'passed'
							when q4.finalgrade_percent <= 59 then 'failed'
							else 'justpassed'
						end
					
					else '[Error - missing iteminfo - student_activity_grade_duedate_status]'
				end
				when q4.status_raw in ('submitted','draft','finished') then 'submitted'
				when q4.status_raw is null or q4.status_raw in ('new','inprogress') then /* check if due */
					case
						when student_duedate_epoch = 0 then 'notsubmitted'
						when extract(epoch from now()) < q4.student_duedate_epoch then 'notdue'
						when extract(epoch from now()) > q4.student_duedate_epoch then 'overdue'
					end
			    else 'unknown ' || q4.status_raw
			end as status
			from q4
		)
		select * from q5
	)
	--select * from student_activity_grade_duedate_status where userid=138996
	,student_grades_from_gradeitems_with_idnumber as (
		with q1 as (
			select s.userid, gi.idnumber,
			g.finalgrade, ((g.finalgrade / g.rawgrademax) * 100)::int as finalgrade_percent, gi.iteminfo, gi.gradepass
			from students1 s
			cross join vars v
			join mdl_grade_grades g on g.userid = s.userid
			join mdl_grade_items gi on gi.id = g.itemid and coalesce(gi.idnumber,'') != '' and gi.courseid = v.course_id
		)
		,q2 as (
			select q1.*,
			/* Note this should match the code in student_activity_grade_duedate_status above */
			case
				when q1.finalgrade is null then 'Not submitted'
				when q1.finalgrade is not null then -- q1.finalgrade_percent || ' ' ||
				case
					/* Default */
					when q1.iteminfo is null or q1.iteminfo = '' or q1.iteminfo = 'marking_category0' then
						case
							when q1.gradepass = 0.0 and q1.finalgrade_percent >= 50 then 'Passed'
							when q1.gradepass != 0.0 and q1.finalgrade >= q1.gradepass then 'Passed'
							else 'Failed'
						end
					/* a.	Te Mahi, Maths Readiness Quiz, Quiz 1:
							Failed = 59% or less / Just passed = 79% or less / Passed = 80% or higher
					*/
					when q1.iteminfo = 'marking_category1' then
						case
							when q1.finalgrade_percent >= 80 then 'Passed'
							when q1.finalgrade_percent <= 59 then 'Failed'
							else 'Just passed'
						end
					/*
						b.	Test 1, Quiz 2:
						Failed = 49% or less / Just passed = 74% or less / Passed = 75% or higher
					*/
					when q1.iteminfo = 'marking_category2' then
						case
							when q1.finalgrade_percent >= 75 then 'Passed'
							when q1.finalgrade_percent <= 49 then 'Failed'
							else 'Just passed'
						end
					when q1.iteminfo = 'marking_category3' then
						case
							when q1.finalgrade_percent >= 65 then 'Passed'
							when q1.finalgrade_percent <= 49 then 'Failed'
							else 'Just passed'
						end
					else '[Error - missing iteminfo]'
				end
			end as status
			from q1
		)
		select * from q2
	)
	--select * from student_activity
	--select * from student_grades_from_gradeitems_with_idnumber
	--select * from student_activity_grade_duedate_status
	select ROW_NUMBER() OVER(order by user_row_index, activity_row_index) as id,
	user_row_index as userid, activity_row_index as assessmentid, status,
	case when student_duedate_extension = 'Yes' then
		student_duedate_epoch else 0 end as extension_date,
	case when finalgrade_percent is null then -1 else finalgrade_percent end as grade

	from student_activity_grade_duedate_status

	order by user_row_index, activity_row_index
)
,early_engagement_activities as (
	select 
	ROW_NUMBER() OVER(order by cm.completionexpected, cm.idnumber) as id,
	cm.id as cmid,
	cm.completionexpected
	from vars v
	join mdl_course_modules cm on cm.course = v.course_id and cm.idnumber ~ 'EE\d'
)
,get_early_engagements as (
	select ee.id, ee.cmid
	from early_engagement_activities ee
)
,get_user_early_engagements as (
	with q1 as (
		select cm.*, s.id as student_id, s.userid
		from early_engagement_activities cm
		cross join students1 s
	)
	select
		ROW_NUMBER() OVER(order by q1.student_id, q1.id) as id,
		q1.student_id as userid,
		q1.id as earlyengagementid,
		case
			when mc.completionstate = 1 then 'completed'
			else
				case
					when q1.completionexpected = 0 then 'notcompleted'
					when to_timestamp(q1.completionexpected) > now() then 'notdue'
					else 'overdue'
			end
		end as status
		from q1
		left join mdl_course_modules_completion mc on mc.coursemoduleid = q1.cmid and mc.userid = q1.userid
		left join excluded_cmids xcm on xcm.id = q1.cmid

		where xcm.id is null

		order by q1.student_id, q1.id
)

        ";
    }
}
