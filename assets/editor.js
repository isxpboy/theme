(function (wp) {
  'use strict';
  const h=wp.element.createElement, Fragment=wp.element.Fragment;
  const {InspectorControls,useBlockProps}=wp.blockEditor;
  const {PanelBody,ToggleControl,TextControl,SelectControl,RangeControl,Placeholder}=wp.components;
  const ServerRender=wp.serverSideRender?.default||wp.serverSideRender;
  const labels={animated:'循环书写动画',label:'按钮文字',title:'目录标题',name:'名称（留空继承站点名称）',showCategory:'显示分类',showDate:'显示日期',showReadingTime:'显示阅读时间',showReader:'显示朗读按钮',open:'默认展开目录',github:'GitHub 地址',x:'Twitter / X 地址',instagram:'Instagram 地址',showRss:'显示 RSS 订阅',showMap:'显示文章地图',showXml:'显示 XML 地图',showPages:'包括公开页面'};
  for(const metadata of window.LeiBlockDefinitions||[]){
    wp.blocks.registerBlockType(metadata.name,{
      apiVersion:3,title:metadata.title,description:metadata.description,category:metadata.category,icon:metadata.icon,attributes:metadata.attributes,supports:metadata.supports,usesContext:metadata.usesContext,
      edit:function(props){
        const controls=Object.entries(metadata.attributes||{}).map(([key,definition])=>{
          const base={key,label:labels[key]||key,__nextHasNoMarginBottom:true};
          if(key==='variant')return h(SelectControl,{...base,label:'标识样式',value:props.attributes[key],options:[{label:'手写 Lei 字标',value:'signature'},{label:'站点文字名称',value:'text'},{label:'站点自定义 Logo',value:'custom-logo'}],onChange:value=>props.setAttributes({[key]:value})});
          if(definition.type==='boolean')return h(ToggleControl,{...base,checked:!!props.attributes[key],onChange:value=>props.setAttributes({[key]:value})});
          if(key==='perPage')return h(RangeControl,{...base,label:'每页文章数量',min:10,max:200,value:props.attributes[key],onChange:value=>props.setAttributes({[key]:value})});
          return h(TextControl,{...base,__next40pxDefaultSize:true,type:['github','x','instagram'].includes(key)?'url':'text',help:['github','x','instagram'].includes(key)?'留空继承主题设置；未配置时保留图标占位。':undefined,value:props.attributes[key]||'',onChange:value=>props.setAttributes({[key]:value})});
        });
        return h(Fragment,null,h(InspectorControls,null,h(PanelBody,{title:'区块设置',initialOpen:true},controls)),h('div',useBlockProps(),ServerRender?h(ServerRender,{block:metadata.name,attributes:props.attributes,EmptyResponsePlaceholder:()=>h(Placeholder,{label:metadata.title},metadata.description)}):h(Placeholder,{label:metadata.title},metadata.description)));
      },save:()=>null
    });
  }
  const languages=[{label:'纯文本',value:'plain'},{label:'JavaScript',value:'javascript'},{label:'TypeScript',value:'typescript'},{label:'YAML',value:'yaml'},{label:'Shell / Bash',value:'bash'},{label:'JSON',value:'json'},{label:'Python',value:'python'},{label:'PHP',value:'php'},{label:'HTML',value:'markup'},{label:'CSS',value:'css'},{label:'Go',value:'go'},{label:'SQL',value:'sql'}];
  wp.hooks.addFilter('blocks.registerBlockType','lei-notes/code-attributes',(settings,name)=>name==='core/code'?{...settings,attributes:{...settings.attributes,leiLanguage:{type:'string',default:'plain'},leiFilename:{type:'string',default:''}}}:settings);
  wp.hooks.addFilter('editor.BlockEdit','lei-notes/code-controls',wp.compose.createHigherOrderComponent(BlockEdit=>props=>{
    if(props.name!=='core/code')return h(BlockEdit,props);
    return h(Fragment,null,h(BlockEdit,props),h(InspectorControls,null,h(PanelBody,{title:'代码展示',initialOpen:true},h(SelectControl,{label:'代码语言',value:props.attributes.leiLanguage||'plain',options:languages,onChange:value=>props.setAttributes({leiLanguage:value}),__nextHasNoMarginBottom:true}),h(TextControl,{label:'文件名（可选）',placeholder:'例如 compose.yaml',value:props.attributes.leiFilename||'',onChange:value=>props.setAttributes({leiFilename:value}),__nextHasNoMarginBottom:true,__next40pxDefaultSize:true}))));
  },'LeiNotesCodeControls'));
})(window.wp);
