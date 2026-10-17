/* ค้นหาแบบ AND: ทุกคำต้องตรงอย่างน้อยหนึ่งข้อมูล/คำที่มีความหมายใกล้กัน */
(function(root){
 const normalize=value=>String(value??'').normalize('NFKC').toLocaleLowerCase('th').replace(/[\u200B-\u200D\uFEFF]/g,'').replace(/\s+/g,' ').trim();
 const groups=[['คาเฟ่','คาเฟ','คาเฟ่ต์','ร้านกาแฟ','กาแฟ','cafe','café','coffee'],['ชายหาด','หาด','beach'],['ทะเล','ชายทะเล','ริมทะเล','sea','seaside'],['วัด','ไหว้พระ','ทำบุญ','temple'],['เกาะ','island'],['ตลาด','market'],['พิพิธภัณฑ์','museum'],['สวนน้ำ','waterpark','water park'],['สวนสัตว์','zoo'],['จุดชมวิว','viewpoint'],['ศรีราชา','sriracha','si racha'],['บางละมุง','bang lamung'],['สัตหีบ','sattahip'],['พัทยา','pattaya'],['บางแสน','bangsaen','bang saen']].map(g=>g.map(normalize));
 const alternatives=term=>groups.find(g=>g.includes(term))||[term];
 function tokens(query){let q=normalize(query);for(const phrase of ['water park','si racha','bang lamung','bang saen'])q=q.replaceAll(phrase,phrase.replaceAll(' ','_'));return q.split(/[\s,，]+/u).filter(Boolean).map(t=>t.replaceAll('_',' '));}
 function matches(place,query){const fields=[place.name,place.category,place.district,place.address,place.history,place.highlight,Array.isArray(place.keywords)?place.keywords.join(' '):place.keywords];const text=normalize(fields.filter(Boolean).join(' ').replace(/จังหวัด/g,' '));return tokens(query).every(term=>alternatives(term).some(word=>text.includes(word)));}
 const api={normalize,tokens,matches};root.TravelSearch=api;if(typeof module!=='undefined'&&module.exports)module.exports=api;
})(typeof window!=='undefined'?window:globalThis);
