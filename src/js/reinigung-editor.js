import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { PanelBody, CheckboxControl, TextareaControl, Spinner, Button, Flex, FlexItem } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { createElement, Fragment, useMemo, useCallback } from '@wordpress/element';
import '../css/reinigung-editor.scss';

const ensureObj = (v) => (v && typeof v === 'object') ? v : {};
const normalizeTasks = (tasks) => Array.isArray(tasks) ? tasks : Object.keys(tasks || {});
const toggleTaskIn = (state, bereich, aufgabe) => {
  const cur = ensureObj(state[bereich]);
  return { ...state, [bereich]: { ...cur, [aufgabe]: !cur[aufgabe] } };
};
const setAllIn = (state, bereich, tasks, value) => {
  const next = { ...ensureObj(state[bereich]) };
  tasks.forEach((t) => { next[t] = !!value; });
  return { ...state, [bereich]: next };
};

const ReinigungPanel = () => {
  // Sicherstellen, dass wir wirklich im CPT "reinigung" sind
  const { postType } = useSelect( ( select ) => {
    const editor = select( 'core/editor' );
    return {
      postType: editor?.getCurrentPostType?.(),
    };
  }, [] );
  if (postType && postType !== 'reinigung') return null;

  const [meta, setMeta] = useEntityProp('postType', 'reinigung', 'meta');
  const defaultsFromPhp = (window.udReinigungDefaults && window.udReinigungDefaults.bereiche) || {};
  const defaults = ensureObj(defaultsFromPhp);

  // Wenn Meta noch nicht da, Spinner
  if (!meta) return <Spinner />;

  const checklisten = ensureObj(meta?.checklisten);
  const bemerkungen = meta?.bemerkungen ?? '';

  const bereiche = useMemo(() => Object.keys(defaults), [defaults]);

  const onToggle = useCallback((bereich, aufgabe) => {
    const next = toggleTaskIn(checklisten, bereich, aufgabe);
    setMeta({ ...meta, checklisten: next });
  }, [checklisten, meta, setMeta]);

  const onAll = useCallback((bereich, tasks, value) => {
    const next = setAllIn(checklisten, bereich, tasks, value);
    setMeta({ ...meta, checklisten: next });
  }, [checklisten, meta, setMeta]);

  return (
    <PluginDocumentSettingPanel
      name="ud-reinigung"
      title="Reinigung"
      className="ud-reinigung-panel"
    >
      {bereiche.length === 0 && (
        <p style={{ opacity: .8 }}>
          Keine Checklisten-Defaults gefunden. Prüfe <code>ud_reinigung_get_default_checklisten()</code> / <code>wp_localize_script</code>.
        </p>
      )}

      {bereiche.map((bereich, idx) => {
        const tasks = normalizeTasks(defaults[bereich]);
        const allChecked = tasks.length > 0 && tasks.every(t => !!checklisten?.[bereich]?.[t]);
        const someChecked = !allChecked && tasks.some(t => !!checklisten?.[bereich]?.[t]);

        return (
          <PanelBody key={bereich} title={bereich} initialOpen={idx === 0}>
            <Flex align="center" justify="flex-start" gap={8} className="ud-reinigung-toolbar">
              <FlexItem>
                <Button
                  variant={allChecked ? 'secondary' : 'primary'}
                  onClick={() => onAll(bereich, tasks, !allChecked)}
                  size="small"
                >
                  {allChecked ? 'Alle abwählen' : 'Alle anwählen'}
                </Button>
              </FlexItem>
              {someChecked && <span style={{ opacity: .7, fontSize: 12 }}>teilweise ausgewählt</span>}
            </Flex>

            {tasks.map((aufgabe) => (
              <CheckboxControl
                key={aufgabe}
                label={aufgabe}
                checked={!!checklisten?.[bereich]?.[aufgabe]}
                onChange={() => onToggle(bereich, aufgabe)}
              />
            ))}
            {tasks.length === 0 && <p style={{ opacity: .7 }}>Keine Aufgaben in diesem Bereich.</p>}
          </PanelBody>
        );
      })}

      <TextareaControl
        label="Bemerkungen"
        value={bemerkungen}
        onChange={(v) => setMeta({ ...meta, bemerkungen: v })}
        rows={5}
      />
    </PluginDocumentSettingPanel>
  );
};

registerPlugin('ud-reinigung-panel', { render: ReinigungPanel });
