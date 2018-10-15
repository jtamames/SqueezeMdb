SELECT Bin.id as bin_id, Contig.id as contig_id, Gene.id as gene_id
-- Bin.name AS Bin_name, Contig.name AS Contig_name, Gene.name AS Gene_name, Gene.taxonomy AS Gene_taxonomy
FROM  Gene JOIN Sample_Gene ON (Gene.id=Sample_Gene.gene_id) 
JOIN Sample ON (Sample_Gene.sample_ID=Sample.id)
JOIN Contig ON (Gene.contig_id=Contig.id)  
JOIN Bin_Contig ON (Bin_Contig.contig_id=Contig.id) 
JOIN Bin ON (Bin.id=Bin_Contig.bin_id)  
WHERE Sample.project_ID=5 AND (Gene.COG_function  LIKE '%heat%')
GROUP BY  Bin.id, Contig.id, Gene.id
-- ,  gabun.IC1025022_norm_counts, gabun.IC102520_norm_counts, gabun.IC10253_norm_counts, gabun.IC1040022_norm_counts, gabun.IC10403_norm_counts, gabun.IC125022_norm_counts, gabun.IC1253_norm_counts, gabun.IC13253_norm_counts, gabun.IC1525022_norm_counts, gabun.IC152520_norm_counts, gabun.IC15253_norm_counts, gabun.IC1825022_norm_counts, gabun.IC182520_norm_counts, gabun.IC18253_norm_counts, gabun.IC225022_norm_counts, gabun.IC2253_norm_counts, gabun.IC325022_norm_counts, gabun.IC3253_norm_counts, gabun.IC425022_norm_counts, gabun.IC4253_norm_counts, gabun.IC440022_norm_counts, gabun.IC4403_norm_counts, gabun.IC5253_norm_counts, gabun.IC625022_norm_counts;


SELECT
	Gene.name AS Gene_name, Gene.taxonomy AS Gene_taxonomy
     ,Bin.name AS Bin_name
     , Contig.name AS Contig_name
     , gabun.*
FROM (
	SELECT Bin.id as bin_id, Contig.id as contig_id, Gene.id as gene_id
	FROM  Gene JOIN Sample_Gene ON (Gene.id=Sample_Gene.gene_id) 
	inner JOIN Sample ON (Sample_Gene.sample_ID=Sample.id)
	inner JOIN Contig ON (Gene.contig_id=Contig.id)  
	inner JOIN Bin_Contig ON (Bin_Contig.contig_id=Contig.id) 
	inner JOIN Bin ON (Bin.id=Bin_Contig.bin_id)  
	WHERE Sample.project_ID=5 AND (Gene.COG_function  LIKE '%heat%')
	GROUP BY  Bin.id, Contig.id, Gene.id
) ids INNER JOIN Gene gene ON (ids.gene_id=gene.id)
 INNER JOIN Contig contig ON (ids.contig_id=contig.id)
 INNER JOIN Bin bin ON (ids.bin_id=Bin.id)
 INNER JOIN (
 SELECT gene_id, sum( if( sam.name = 'IC1025022',sg.norm_counts,0)) as IC1025022_norm_counts, sum( if( sam.name = 'IC102520',sg.norm_counts,0)) as IC102520_norm_counts
    , sum( if( sam.name = 'IC10253',sg.norm_counts,0)) as IC10253_norm_counts, sum( if( sam.name = 'IC1040022'
    ,sg.norm_counts,0)) as IC1040022_norm_counts, sum( if( sam.name = 'IC10403',sg.norm_counts,0)) as IC10403_norm_counts
    , sum( if( sam.name = 'IC125022',sg.norm_counts,0)) as IC125022_norm_counts, sum( if( sam.name = 'IC1253',sg.norm_counts,0)) as IC1253_norm_counts
    , sum( if( sam.name = 'IC13253',sg.norm_counts,0)) as IC13253_norm_counts, sum( if( sam.name = 'IC1525022',sg.norm_counts,0)) as IC1525022_norm_counts
    , sum( if( sam.name = 'IC152520',sg.norm_counts,0)) as IC152520_norm_counts, sum( if( sam.name = 'IC15253',sg.norm_counts,0)) as IC15253_norm_counts
    , sum( if( sam.name = 'IC1825022',sg.norm_counts,0)) as IC1825022_norm_counts, sum( if( sam.name = 'IC182520',sg.norm_counts,0)) as IC182520_norm_counts
    , sum( if( sam.name = 'IC18253',sg.norm_counts,0)) as IC18253_norm_counts, sum( if( sam.name = 'IC225022',sg.norm_counts,0)) as IC225022_norm_counts
    , sum( if( sam.name = 'IC2253',sg.norm_counts,0)) as IC2253_norm_counts, sum( if( sam.name = 'IC325022',sg.norm_counts,0)) as IC325022_norm_counts
    , sum( if( sam.name = 'IC3253',sg.norm_counts,0)) as IC3253_norm_counts, sum( if( sam.name = 'IC425022',sg.norm_counts,0)) as IC425022_norm_counts
    , sum( if( sam.name = 'IC4253',sg.norm_counts,0)) as IC4253_norm_counts, sum( if( sam.name = 'IC440022',sg.norm_counts,0)) as IC440022_norm_counts
    , sum( if( sam.name = 'IC4403',sg.norm_counts,0)) as IC4403_norm_counts, sum( if( sam.name = 'IC5253',sg.norm_counts,0)) as IC5253_norm_counts
    , sum( if( sam.name = 'IC625022',sg.norm_counts,0)) as IC625022_norm_counts 
    FROM Sample_Gene sg JOIN Sample sam ON (sg.Sample_id=sam.id) 
    WHERE sg.gene_id in (
		SELECT Gene.id as gene_id
		FROM  Gene JOIN Sample_Gene ON (Gene.id=Sample_Gene.gene_id) 
		inner JOIN Sample ON (Sample_Gene.sample_ID=Sample.id)
		inner JOIN Contig ON (Gene.contig_id=Contig.id)  
		inner JOIN Bin_Contig ON (Bin_Contig.contig_id=Contig.id) 
		inner JOIN Bin ON (Bin.id=Bin_Contig.bin_id)  
		WHERE Sample.project_ID=5 AND (Gene.COG_function  LIKE '%heat%')
		GROUP BY  Gene.id
    )
    GROUP BY gene_id
 ) gabun on ids.gene_id=gabun.gene_id