import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel, store as editorStore } from '@wordpress/editor';
import { SelectControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';

const TYPES = [
	{ label: 'Article', value: 'Article' },
	{ label: 'Blog posting', value: 'BlogPosting' },
	{ label: 'Web page', value: 'WebPage' },
	{ label: 'None', value: 'none' },
];

function SchemaPanel() {
	const postType = useSelect((select) => select(editorStore).getCurrentPostType(), []);
	const [meta, setMeta] = useEntityProp('postType', postType, 'meta');

	if (!meta || !('_schema_ld_type' in meta)) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel name="schema-ld" title="Schema">
			<SelectControl
				label="Schema type"
				value={meta._schema_ld_type}
				options={TYPES}
				onChange={(value) => setMeta({ ...meta, _schema_ld_type: value })}
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>
		</PluginDocumentSettingPanel>
	);
}

registerPlugin('schema-ld', { render: SchemaPanel });
