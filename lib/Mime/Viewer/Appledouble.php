<?php
/**
 * The IMP_Horde_Mime_Viewer_Appledouble class handles multipart/appledouble
 * messages conforming to RFC 1740.
 *
 * Copyright 2003-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package Horde_Mime
 */
class IMP_Horde_Mime_Viewer_Appledouble extends Horde_Mime_Viewer_Driver
{
    /**
     * This driver's capabilities.
     *
     * @var boolean
     */
    protected $_capability = array(
        'embedded' => false,
        'forceinline' => true,
        'full' => false,
        'info' => true,
        'inline' => true,
        'raw' => false
    );

    /**
     * Return the rendered inline version of the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInline()
    {
        return $this->_IMPrender(true);
    }

    /**
     * Return the rendered information about the Horde_Mime_Part object.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _renderInfo()
    {
        return $this->_IMPrender(false);
    }

    /**
     * Render the part based on the view mode.
     *
     * @param boolean $inline  True if viewing inline.
     *
     * @return array  See Horde_Mime_Viewer_Driver::render().
     */
    protected function _IMPrender($inline)
    {
        /* RFC 1740 [4]: There are two parts to an appledouble message:
         *   (1) application/applefile
         *   (2) Data embedded in the Mac file
         * Since the resource fork is not very useful to us, only provide a
         * means to download. */

        /* Display the resource fork download link. */
        $mime_id = $this->_mimepart->getMimeId();
        $parts_list = array_keys($this->_mimepart->contentTypeMap());
        reset($parts_list);
        $applefile_id = next($parts_list);
        $data_id = Horde_Mime::mimeIdArithmetic($applefile_id, 'next');

        $applefile_part = $this->_mimepart->getPart($applefile_id);
        $data_part = $this->_mimepart->getPart($data_id);

        $data_name = $data_part->getName(true);
        if (empty($data_name)) {
            $data_name = _("unnamed");
        }

        $status = array(
            'icon' => Horde::img('apple.png', _("Macintosh File")),
            'text' => array(
                sprintf(_("This message contains a Macintosh file (named \"%s\")."), $data_name),
                sprintf(_("The Macintosh resource fork can be downloaded %s."), $this->_params['contents']->linkViewJS($applefile_part, 'download_attach', _("HERE"), array('jstext' => _("The Macintosh resource fork"))))
            )
        );

        /* For inline viewing, attempt to display the data inline. */
        $ret = array();
        if ($inline && (($disp = $this->_params['contents']->canDisplay($data_part, IMP_Contents::RENDER_INLINE | IMP_Contents::RENDER_INFO)))) {
            $ret = $this->_params['contents']->renderMIMEPart($data_id, $disp, array('params' => $this->_params));
            $status['text'][] = _("The contents of the Macintosh file are below.");
        } else {
            $status['text'][] = sprintf(_("The contents of the Macintosh file can be downloaded %s."), $this->_params['contents']->linkViewJS($data_part, 'download_attach', _("HERE"), array('jstext' => _("The Macintosh file"))));
        }

        foreach ($parts_list as $val) {
            if (!isset($ret[$val]) && (strcmp($val, $data_id) !== 0)) {
                $ret[$val] = (strcmp($val, $mime_id) === 0)
                    ? array(
                          'data' => '',
                          'status' => array($status),
                          'type' => 'text/html; charset=' . Horde_Nls::getCharset()
                      )
                    : null;
            }
        }

        ksort($ret);

        return $ret;
    }
}
