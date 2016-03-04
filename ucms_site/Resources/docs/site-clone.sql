
-- first, copy node references
-- explain tells us we only have const indexes which is very good news for us
EXPLAIN
INSERT INTO {ucms_site_node} (site_id, nid)
SELECT
    :target, usn.nid
FROM {ucms_site_node} usn
JOIN {node} n
WHERE
    usn.site_id = :source
    -- do not reference unpublished global content
    AND n.status = 1
    -- and avoid duplicates
    AND NOT EXISTS (
        SELECT 1
        FROM {ucms_site_node} s_usn
        WHERE
            s_usn.nid = usn.nid
            AND s_usn.site_id = :target
    )
;

-- then, copy node layouts
-- only const indexes too
EXPLAIN
INSERT INTO {ucms_layout} (site_id, nid)
SELECT
    :target, usn.nid
FROM {ucms_layout} ul
-- only create layouts when node is referenced on site
JOIN {ucms_site_node} usn ON
    usn.nid = usn.nid
    AND usn.site_id = :target
WHERE
    ul.site_id = :source
    -- avoid duplicates
    AND NOT EXISTS (
        SELECT 1
        FROM {ucms_layout} s_ul
        WHERE
            s_ul.nid = ul.nid
            AND s_ul.site_id = :target
    )
;

-- layout data, very important too
-- this one will generate a temporary query, but because we do filter a lot
-- the data source, it should be as fast as a small colibri breath
EXPLAIN
INSERT INTO {ucms_layout_data}
        (layout_id, region, nid, weight, view_mode)
SELECT
    target_ul.id,
    uld.region,
    uld.nid,
    uld.weight,
    uld.view_mode
-- tells us where to fetch the layout
FROM {ucms_layout} source_ul
JOIN {ucms_layout_data} uld ON
    source_ul.nid = uld.nid
    AND source_ul.site_id = :source
-- for the same reason as above, do not reference unpublished global content
-- only populate existing layouts on target
JOIN {node} n ON n.nid = uld.nid
-- and copy compositions only when the layout exists
JOIN {ucms_layout} target_ul ON
    target_ul.nid = uld.nid
    AND target_ul.site_id = :target
WHERE
    n.status = 1
;

-- @todo
--   menus !
--   composite types must be cloned directly
--   any other thing ?
--   home page must be cloned, but already exists

